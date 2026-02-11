import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:flutter/foundation.dart' show kIsWeb;
import 'package:provider/provider.dart';
import 'package:mobile_scanner/mobile_scanner.dart';
import '../providers/gallon_provider.dart';

class ReturnGallonScreen extends StatefulWidget {
  @override
  _ReturnGallonScreenState createState() => _ReturnGallonScreenState();
}

class _ReturnGallonScreenState extends State<ReturnGallonScreen> {
  bool _showScanner = false;
  bool _showManualEntry = false;
  bool _isProcessingQR = false;
  String _detectedQR = '';
  final TextEditingController _manualCodeController = TextEditingController();
  MobileScannerController? _scannerController;

  @override
  void initState() {
    super.initState();
    _scannerController = MobileScannerController(
      detectionSpeed: DetectionSpeed.normal,
    );
  }

  @override
  void dispose() {
    _scannerController?.dispose();
    _manualCodeController.dispose();
    super.dispose();
  }

  void _onScanDetect(BarcodeCapture capture) async {
    if (_showScanner && !_isProcessingQR && capture.barcodes.isNotEmpty) {
      final rawValue = capture.barcodes.first.rawValue;
      if (rawValue != null && rawValue.isNotEmpty) {
        setState(() {
          _isProcessingQR = true;
          _detectedQR = rawValue;
        });
        
        // Add delay for visual feedback
        await Future.delayed(Duration(milliseconds: 500));
        
        // Extract gallon code from QR data
        final gallonCode = _extractGallonCode(rawValue);
        print('========================================');
        print('QR SCAN DEBUG:');
        print('Raw Value: "$rawValue"');
        print('Extracted Code: "$gallonCode"');
        print('Code Length: ${gallonCode.length}');
        print('========================================');
        
        await _processGallonReturn(gallonCode);
        
        // Reset processing state to allow next scan
        await Future.delayed(Duration(milliseconds: 800));
        setState(() {
          _isProcessingQR = false;
          _detectedQR = '';
        });
      }
    }
  }

  // Extract gallon code from QR data
  // Handles:
  // 1. JSON format: {"code":"GAL001","type":"gallon",...}
  // 2. URLs: http://example.com/gallon/WR-GAL-0001
  // 3. Plain codes: WR-GAL-0001 or GAL001
  String _extractGallonCode(String rawValue) {
    String cleaned = rawValue.trim();
    
    // Try to parse as JSON first
    try {
      final jsonData = jsonDecode(cleaned);
      if (jsonData is Map && jsonData.containsKey('code')) {
        cleaned = jsonData['code'].toString();
      }
    } catch (e) {
      // Not JSON, continue with other parsing methods
    }
    
    // If it's a URL, extract the last segment
    if (cleaned.contains('://') || cleaned.contains('/')) {
      final segments = cleaned.split('/');
      cleaned = segments.last;
    }
    
    // Remove any query parameters
    if (cleaned.contains('?')) {
      cleaned = cleaned.split('?').first;
    }
    
    // Remove any hash fragments
    if (cleaned.contains('#')) {
      cleaned = cleaned.split('#').first;
    }
    
    cleaned = cleaned.trim();
    
    // Normalize the gallon code format
    // If code doesn't start with "WR-", add it as prefix
    if (!cleaned.toUpperCase().startsWith('WR-')) {
      // Remove any existing prefix like "GAL" and extract just the number
      final match = RegExp(r'(\d+)').firstMatch(cleaned);
      if (match != null) {
        final number = match.group(1)!.padLeft(4, '0');
        cleaned = 'WR-GAL-$number';
      } else {
        // If no number found, assume it's already in correct format
        cleaned = 'WR-GAL-$cleaned';
      }
    }
    
    return cleaned;
  }

  Future<void> _processGallonReturn(String gallonCode) async {
    final confirm = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: Text('Return Gallon'),
        content: Text('Return gallon $gallonCode?'),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: Text('Cancel'),
          ),
          ElevatedButton(
            onPressed: () => Navigator.pop(context, true),
            child: Text('Confirm'),
          ),
        ],
      ),
    );

    if (confirm == true && mounted) {
      final gallonProvider = Provider.of<GallonProvider>(context, listen: false);
      final success = await gallonProvider.returnGallon(gallonCode);

      if (success && mounted) {
        setState(() {
          _showManualEntry = false;
          _manualCodeController.clear();
        });
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('Gallon $gallonCode returned successfully'),
            backgroundColor: Colors.green,
          ),
        );
      } else if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(gallonProvider.error ?? 'Failed to return gallon'),
            backgroundColor: Colors.red,
          ),
        );
      }
    } else if (mounted) {
      setState(() {
        _showManualEntry = false;
        _manualCodeController.clear();
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text('Return Gallon'),
      ),
      body: _showScanner 
          ? _buildScanner() 
          : _showManualEntry 
              ? _buildManualEntry() 
              : _buildInstructions(),
      floatingActionButton: (_showScanner || _showManualEntry) ? null : Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          FloatingActionButton(
            heroTag: 'manual',
            onPressed: () => setState(() => _showManualEntry = true),
            child: Icon(Icons.keyboard),
            backgroundColor: Colors.orange,
          ),
          SizedBox(height: 12),
          FloatingActionButton.extended(
            heroTag: 'scan',
            onPressed: () => setState(() => _showScanner = true),
            icon: Icon(Icons.qr_code_scanner),
            label: Text('Scan QR'),
          ),
        ],
      ),
    );
  }

  Widget _buildScanner() {
    return Stack(
      children: [
        MobileScanner(
          controller: _scannerController,
          onDetect: _onScanDetect,
          errorBuilder: (context, error, child) {
            return Center(
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  Icon(Icons.error_outline, size: 64, color: Colors.red),
                  SizedBox(height: 16),
                  Text(
                    'Camera not available',
                    style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
                  ),
                  SizedBox(height: 8),
                  Padding(
                    padding: EdgeInsets.symmetric(horizontal: 32),
                    child: Text(
                      kIsWeb 
                        ? 'Please allow camera permissions in your browser'
                        : error.toString(),
                      style: TextStyle(color: Colors.grey[600]),
                      textAlign: TextAlign.center,
                    ),
                  ),
                  SizedBox(height: 24),
                  ElevatedButton.icon(
                    onPressed: () {
                      setState(() {
                        _showScanner = false;
                        _showManualEntry = true;
                      });
                    },
                    icon: Icon(Icons.keyboard),
                    label: Text('Use Manual Entry Instead'),
                    style: ElevatedButton.styleFrom(
                      padding: EdgeInsets.symmetric(horizontal: 24, vertical: 12),
                    ),
                  ),
                ],
              ),
            );
          },
        ),
        Positioned(
          top: 16,
          left: 16,
          right: 16,
          child: Card(
            child: Padding(
              padding: EdgeInsets.all(16),
              child: Column(
                children: [
                  Icon(Icons.qr_code_scanner, size: 48, color: Theme.of(context).primaryColor),
                  SizedBox(height: 8),
                  Text(
                    'Scan QR Code to Return Gallon',
                    textAlign: TextAlign.center,
                    style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
                  ),
                  if (kIsWeb) ...[
                    SizedBox(height: 8),
                    Text(
                      'Having trouble? Use manual entry below',
                      style: TextStyle(fontSize: 12, color: Colors.grey[600]),
                      textAlign: TextAlign.center,
                    ),
                  ],
                ],
              ),
            ),
          ),
        ),
        Positioned(
          bottom: 32,
          left: 0,
          right: 0,
          child: Column(
            children: [
              if (kIsWeb)
                Padding(
                  padding: EdgeInsets.symmetric(horizontal: 32, vertical: 8),
                  child: ElevatedButton.icon(
                    onPressed: () {
                      setState(() {
                        _showScanner = false;
                        _showManualEntry = true;
                      });
                    },
                    icon: Icon(Icons.keyboard),
                    label: Text('Enter Code Manually'),
                    style: ElevatedButton.styleFrom(
                      padding: EdgeInsets.symmetric(horizontal: 24, vertical: 12),
                      backgroundColor: Colors.blue,
                    ),
                  ),
                ),
              ElevatedButton(
                onPressed: () => setState(() => _showScanner = false),
                style: ElevatedButton.styleFrom(
                  padding: EdgeInsets.symmetric(horizontal: 32, vertical: 16),
                  backgroundColor: Colors.red,
                ),
                child: Text('Cancel'),
              ),
            ],
          ),
        ),
        // QR Detection Overlay
        if (_isProcessingQR)
          Container(
            color: Colors.black54,
            child: Center(
              child: Card(
                margin: EdgeInsets.all(32),
                child: Padding(
                  padding: EdgeInsets.all(24),
                  child: Column(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Icon(
                        Icons.check_circle,
                        size: 64,
                        color: Colors.green,
                      ),
                      SizedBox(height: 16),
                      Text(
                        'QR Code Detected!',
                        style: TextStyle(
                          fontSize: 20,
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                      SizedBox(height: 8),
                      Text(
                        _detectedQR.length > 30 
                            ? '${_detectedQR.substring(0, 30)}...'
                            : _detectedQR,
                        style: TextStyle(
                          color: Colors.grey[600],
                          fontSize: 12,
                        ),
                        textAlign: TextAlign.center,
                      ),
                      SizedBox(height: 16),
                      CircularProgressIndicator(),
                    ],
                  ),
                ),
              ),
            ),
          ),
      ],
    );
  }

  Widget _buildManualEntry() {
    return Padding(
      padding: EdgeInsets.all(24),
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Icon(Icons.qr_code, size: 80, color: Theme.of(context).primaryColor),
          SizedBox(height: 24),
          Text(
            'Enter Gallon Code Manually',
            style: TextStyle(fontSize: 20, fontWeight: FontWeight.bold),
          ),
          SizedBox(height: 16),
          Text(
            'Type the gallon ID from the QR code',
            style: TextStyle(color: Colors.grey[600]),
          ),
          SizedBox(height: 32),
          TextField(
            controller: _manualCodeController,
            decoration: InputDecoration(
              labelText: 'Gallon Code',
              hintText: 'e.g., GAL001',
              border: OutlineInputBorder(),
              prefixIcon: Icon(Icons.qr_code_2),
            ),
            textCapitalization: TextCapitalization.characters,
            autofocus: true,
          ),
          SizedBox(height: 24),
          Row(
            children: [
              Expanded(
                child: OutlinedButton(
                  onPressed: () {
                    _manualCodeController.clear();
                    setState(() => _showManualEntry = false);
                  },
                  child: Text('Cancel'),
                  style: OutlinedButton.styleFrom(
                    padding: EdgeInsets.symmetric(vertical: 16),
                  ),
                ),
              ),
              SizedBox(width: 16),
              Expanded(
                child: ElevatedButton(
                  onPressed: () async {
                    final code = _manualCodeController.text.trim();
                    if (code.isNotEmpty) {
                      await _processGallonReturn(code);
                    }
                  },
                  child: Text('Submit'),
                  style: ElevatedButton.styleFrom(
                    padding: EdgeInsets.symmetric(vertical: 16),
                  ),
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildInstructions() {
    return Consumer<GallonProvider>(
      builder: (context, provider, _) {
        return SingleChildScrollView(
          padding: EdgeInsets.all(16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              Card(
                color: Colors.blue[50],
                child: Padding(
                  padding: EdgeInsets.all(16),
                  child: Column(
                    children: [
                      Icon(Icons.info_outline, size: 48, color: Colors.blue),
                      SizedBox(height: 16),
                      Text(
                        'How to Return a Gallon',
                        style: TextStyle(
                          fontSize: 18,
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                      SizedBox(height: 12),
                      Text(
                        '1. Tap the "Scan QR Code" button below\n'
                        '2. Point your camera at the QR code on the gallon\n'
                        '3. Confirm the return when prompted\n'
                        '4. The gallon status will be updated to "IN"',
                        style: TextStyle(fontSize: 14),
                      ),
                    ],
                  ),
                ),
              ),
              SizedBox(height: 24),

              // Gallon Status Summary
              if (provider.statusSummary != null) ...[
                Text(
                  'Current Status',
                  style: TextStyle(
                    fontSize: 18,
                    fontWeight: FontWeight.bold,
                  ),
                ),
                SizedBox(height: 12),
                Row(
                  children: [
                    Expanded(
                      child: _buildStatusCard(
                        'In Station',
                        '${provider.statusSummary!['in_station']}',
                        Colors.green,
                      ),
                    ),
                    SizedBox(width: 12),
                    Expanded(
                      child: _buildStatusCard(
                        'Out',
                        '${provider.statusSummary!['out']}',
                        Colors.orange,
                      ),
                    ),
                  ],
                ),
                SizedBox(height: 12),
                Row(
                  children: [
                    Expanded(
                      child: _buildStatusCard(
                        'Overdue',
                        '${provider.statusSummary!['overdue']}',
                        Colors.red,
                      ),
                    ),
                    SizedBox(width: 12),
                    Expanded(
                      child: _buildStatusCard(
                        'Missing',
                        '${provider.statusSummary!['missing']}',
                        Colors.grey,
                      ),
                    ),
                  ],
                ),
              ],
              SizedBox(height: 80), // Space for FAB
            ],
          ),
        );
      },
    );
  }

  Widget _buildStatusCard(String title, String value, Color color) {
    return Card(
      child: Padding(
        padding: EdgeInsets.all(16),
        child: Column(
          children: [
            Text(
              value,
              style: TextStyle(
                fontSize: 32,
                fontWeight: FontWeight.bold,
                color: color,
              ),
            ),
            SizedBox(height: 4),
            Text(
              title,
              textAlign: TextAlign.center,
              style: TextStyle(
                fontSize: 14,
                color: Colors.grey[600],
              ),
            ),
          ],
        ),
      ),
    );
  }
}
