import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:flutter/foundation.dart' show kIsWeb;
import 'package:provider/provider.dart';
import 'package:mobile_scanner/mobile_scanner.dart';
import '../providers/gallon_provider.dart';

class ReturnGallonScreen extends StatefulWidget {
  const ReturnGallonScreen({super.key});

  @override
  _ReturnGallonScreenState createState() => _ReturnGallonScreenState();
}

class _ReturnGallonScreenState extends State<ReturnGallonScreen> {
  bool _showScanner = false;
  bool _showManualEntry = false;
  bool _isProcessingQR = false;
  bool _cameraReady = false;
  String _detectedQR = '';
  DateTime? _lastScanTime;
  final TextEditingController _manualCodeController = TextEditingController();
  MobileScannerController? _scannerController;
  
  // Scan cooldown duration
  static const _scanCooldown = Duration(seconds: 2);
  // Camera startup delay
  static const _cameraStartupDelay = Duration(milliseconds: 1500);

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
    // Only scan when camera is ready
    if (!_cameraReady || _showScanner == false || _isProcessingQR || capture.barcodes.isEmpty) {
      return;
    }
    
    // Check cooldown period
    final now = DateTime.now();
    if (_lastScanTime != null && now.difference(_lastScanTime!) < _scanCooldown) {
      return; // Still in cooldown, ignore scan
    }
    
    final barcode = capture.barcodes.first;
    final rawValue = barcode.rawValue;
    
    if (rawValue == null || rawValue.isEmpty) return;
    
    // Check if QR code is within the frame bounds
    if (!_isQRInFrame(barcode, capture.size)) {
      return; // QR code is outside the frame, ignore it
    }
    
    setState(() {
      _isProcessingQR = true;
      _detectedQR = rawValue;
      _lastScanTime = now;
    });
    
    // Add delay for visual feedback
    await Future.delayed(const Duration(milliseconds: 800));
    
    // Extract gallon code from QR data
    final gallonCode = _extractGallonCode(rawValue);
    print('========================================');
    print('QR SCAN DEBUG:');
    print('Raw Value: "$rawValue"');
    print('Extracted Code: "$gallonCode"');
    print('Code Length: ${gallonCode.length}');
    print('========================================');
    
    // Close scanner before showing dialog
    setState(() {
      _showScanner = false;
      _isProcessingQR = false;
      _detectedQR = '';
      _cameraReady = false;
    });
    
    // Process the return after closing scanner
    await _processGallonReturn(gallonCode);
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

  // Check if QR code is within the scanning frame
  bool _isQRInFrame(Barcode barcode, Size imageSize) {
    final corners = barcode.corners;
    if (corners.isEmpty || imageSize.width == 0 || imageSize.height == 0) {
      return true; // If no position data, allow scan
    }
    
    // Calculate frame bounds (250x250 centered)
    final screenWidth = MediaQuery.of(context).size.width;
    final screenHeight = MediaQuery.of(context).size.height;
    final frameSize = 250.0;
    final frameCenterX = screenWidth / 2;
    final frameCenterY = screenHeight / 2;
    final frameLeft = frameCenterX - (frameSize / 2);
    final frameRight = frameCenterX + (frameSize / 2);
    final frameTop = frameCenterY - (frameSize / 2);
    final frameBottom = frameCenterY + (frameSize / 2);
    
    // Check if QR code center is within frame
    double qrCenterX = 0;
    double qrCenterY = 0;
    
    for (var corner in corners) {
      // Convert image coordinates to screen coordinates
      final x = (corner.dx / imageSize.width) * screenWidth;
      final y = (corner.dy / imageSize.height) * screenHeight;
      qrCenterX += x;
      qrCenterY += y;
    }
    
    qrCenterX /= corners.length;
    qrCenterY /= corners.length;
    
    // Check if center is within frame with some tolerance
    final tolerance = 50.0; // Allow some margin
    return qrCenterX >= (frameLeft - tolerance) && 
           qrCenterX <= (frameRight + tolerance) &&
           qrCenterY >= (frameTop - tolerance) && 
           qrCenterY <= (frameBottom + tolerance);
  }

  Future<void> _processGallonReturn(String gallonCode) async {
    final confirm = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Return Gallon'),
        content: Text('Return gallon $gallonCode?'),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: const Text('Cancel'),
          ),
          ElevatedButton(
            onPressed: () => Navigator.pop(context, true),
            child: const Text('Confirm'),
          ),
        ],
      ),
    );

    if (confirm == true && mounted) {
      final gallonProvider = Provider.of<GallonProvider>(context, listen: false);
      final success = await gallonProvider.returnGallon(gallonCode);

      if (success && mounted) {
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
    }
    
    // Always clear manual entry after processing
    if (mounted) {
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
        title: const Text('Return Gallon'),
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
            backgroundColor: Colors.orange,
            child: Icon(Icons.keyboard),
          ),
          const SizedBox(height: 12),
          FloatingActionButton.extended(
            heroTag: 'scan',
            onPressed: () async {
              // Dispose old controller and create new one
              _scannerController?.dispose();
              _scannerController = MobileScannerController(
                detectionSpeed: DetectionSpeed.normal,
              );
              
              setState(() {
                _showScanner = true;
                _cameraReady = false;
              });
              
              // Wait for camera to focus
              await Future.delayed(_cameraStartupDelay);
              
              if (mounted && _showScanner) {
                setState(() => _cameraReady = true);
              }
            },
            icon: const Icon(Icons.qr_code_scanner),
            label: const Text('Scan QR'),
          ),
        ],
      ),
    );
  }

  Widget _buildScanner() {
    return Stack(
      children: [
        // Camera view
        MobileScanner(
          controller: _scannerController,
          onDetect: _onScanDetect,
          errorBuilder: (context, error, child) {
            return Center(
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  const Icon(Icons.error_outline, size: 64, color: Colors.red),
                  const SizedBox(height: 16),
                  const Text(
                    'Camera not available',
                    style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
                  ),
                  const SizedBox(height: 8),
                  Padding(
                    padding: const EdgeInsets.symmetric(horizontal: 32),
                    child: Text(
                      kIsWeb 
                        ? 'Please allow camera permissions in your browser'
                        : error.toString(),
                      style: TextStyle(color: Colors.grey[600]),
                      textAlign: TextAlign.center,
                    ),
                  ),
                  const SizedBox(height: 24),
                  ElevatedButton.icon(
                    onPressed: () {
                      setState(() {
                        _showScanner = false;
                        _showManualEntry = true;
                      });
                    },
                    icon: const Icon(Icons.keyboard),
                    label: const Text('Use Manual Entry Instead'),
                    style: ElevatedButton.styleFrom(
                      padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 12),
                    ),
                  ),
                ],
              ),
            );
          },
        ),
        // Camera focusing indicator
        if (!_cameraReady)
          Container(
            color: Colors.black87,
            child: Center(
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  const CircularProgressIndicator(color: Colors.white),
                  const SizedBox(height: 16),
                  const Text(
                    'Focusing camera...',
                    style: TextStyle(
                      color: Colors.white,
                      fontSize: 16,
                    ),
                  ),
                ],
              ),
            ),
          ),
        // QR Scanner Frame Overlay
        Center(
          child: Container(
            width: 250,
            height: 250,
            decoration: BoxDecoration(
              border: Border.all(
                color: _isProcessingQR ? Colors.green : Colors.white,
                width: 3,
              ),
              borderRadius: BorderRadius.circular(12),
            ),
            child: Stack(
              children: [
                // Corner accents
                Positioned(
                  top: 0,
                  left: 0,
                  child: Container(
                    width: 30,
                    height: 30,
                    decoration: BoxDecoration(
                      border: Border(
                        top: BorderSide(color: _isProcessingQR ? Colors.green : Colors.blue, width: 5),
                        left: BorderSide(color: _isProcessingQR ? Colors.green : Colors.blue, width: 5),
                      ),
                    ),
                  ),
                ),
                Positioned(
                  top: 0,
                  right: 0,
                  child: Container(
                    width: 30,
                    height: 30,
                    decoration: BoxDecoration(
                      border: Border(
                        top: BorderSide(color: _isProcessingQR ? Colors.green : Colors.blue, width: 5),
                        right: BorderSide(color: _isProcessingQR ? Colors.green : Colors.blue, width: 5),
                      ),
                    ),
                  ),
                ),
                Positioned(
                  bottom: 0,
                  left: 0,
                  child: Container(
                    width: 30,
                    height: 30,
                    decoration: BoxDecoration(
                      border: Border(
                        bottom: BorderSide(color: _isProcessingQR ? Colors.green : Colors.blue, width: 5),
                        left: BorderSide(color: _isProcessingQR ? Colors.green : Colors.blue, width: 5),
                      ),
                    ),
                  ),
                ),
                Positioned(
                  bottom: 0,
                  right: 0,
                  child: Container(
                    width: 30,
                    height: 30,
                    decoration: BoxDecoration(
                      border: Border(
                        bottom: BorderSide(color: _isProcessingQR ? Colors.green : Colors.blue, width: 5),
                        right: BorderSide(color: _isProcessingQR ? Colors.green : Colors.blue, width: 5),
                      ),
                    ),
                  ),
                ),
              ],
            ),
          ),
        ),
        // Dark overlay around frame
        Positioned.fill(
          child: IgnorePointer(
            child: Container(
              color: Colors.black38,
              child: Center(
                child: Container(
                  width: 250,
                  height: 250,
                  decoration: BoxDecoration(
                    color: Colors.transparent,
                    borderRadius: BorderRadius.circular(12),
                  ),
                ),
              ),
            ),
          ),
        ),
        Positioned(
          top: 16,
          left: 16,
          right: 16,
          child: Card(
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                children: [
                  Icon(Icons.qr_code_scanner, size: 48, color: Theme.of(context).primaryColor),
                  const SizedBox(height: 8),
                  const Text(
                    'Scan QR Code to Return Gallon',
                    textAlign: TextAlign.center,
                    style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
                  ),
                  if (kIsWeb) ...[
                    const SizedBox(height: 8),
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
                  padding: const EdgeInsets.symmetric(horizontal: 32, vertical: 8),
                  child: ElevatedButton.icon(
                    onPressed: () {
                      setState(() {
                        _showScanner = false;
                        _showManualEntry = true;
                      });
                    },
                    icon: const Icon(Icons.keyboard),
                    label: const Text('Enter Code Manually'),
                    style: ElevatedButton.styleFrom(
                      padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 12),
                      backgroundColor: Colors.blue,
                    ),
                  ),
                ),
              ElevatedButton(
                onPressed: () {
                  setState(() => _showScanner = false);
                },
                style: ElevatedButton.styleFrom(
                  padding: const EdgeInsets.symmetric(horizontal: 32, vertical: 16),
                  backgroundColor: Colors.red,
                ),
                child: const Text('Cancel'),
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
                margin: const EdgeInsets.all(32),
                child: Padding(
                  padding: const EdgeInsets.all(24),
                  child: Column(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      const Icon(
                        Icons.check_circle,
                        size: 64,
                        color: Colors.green,
                      ),
                      const SizedBox(height: 16),
                      const Text(
                        'QR Code Detected!',
                        style: TextStyle(
                          fontSize: 20,
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                      const SizedBox(height: 8),
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
                      const SizedBox(height: 16),
                      const CircularProgressIndicator(),
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
      padding: const EdgeInsets.all(24),
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Icon(Icons.qr_code, size: 80, color: Theme.of(context).primaryColor),
          const SizedBox(height: 24),
          const Text(
            'Enter Gallon Code Manually',
            style: TextStyle(fontSize: 20, fontWeight: FontWeight.bold),
          ),
          const SizedBox(height: 16),
          Text(
            'Type the gallon ID from the QR code',
            style: TextStyle(color: Colors.grey[600]),
          ),
          const SizedBox(height: 32),
          TextField(
            controller: _manualCodeController,
            decoration: const InputDecoration(
              labelText: 'Gallon Code',
              hintText: 'e.g., GAL001',
              border: OutlineInputBorder(),
              prefixIcon: Icon(Icons.qr_code_2),
            ),
            textCapitalization: TextCapitalization.characters,
            autofocus: true,
          ),
          const SizedBox(height: 24),
          Row(
            children: [
              Expanded(
                child: OutlinedButton(
                  onPressed: () {
                    _manualCodeController.clear();
                    setState(() => _showManualEntry = false);
                  },
                  style: OutlinedButton.styleFrom(
                    padding: const EdgeInsets.symmetric(vertical: 16),
                  ),
                  child: Text('Cancel'),
                ),
              ),
              const SizedBox(width: 16),
              Expanded(
                child: ElevatedButton(
                  onPressed: () async {
                    final code = _manualCodeController.text.trim();
                    if (code.isNotEmpty) {
                      await _processGallonReturn(code);
                    }
                  },
                  style: ElevatedButton.styleFrom(
                    padding: const EdgeInsets.symmetric(vertical: 16),
                  ),
                  child: Text('Submit'),
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
          padding: const EdgeInsets.all(16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              Card(
                color: Colors.blue[50],
                child: const Padding(
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
              const SizedBox(height: 24),

              // Gallon Status Summary
              if (provider.statusSummary != null) ...[
                const Text(
                  'Current Status',
                  style: TextStyle(
                    fontSize: 18,
                    fontWeight: FontWeight.bold,
                  ),
                ),
                const SizedBox(height: 12),
                Row(
                  children: [
                    Expanded(
                      child: _buildStatusCard(
                        'In Station',
                        '${provider.statusSummary!['in_station']}',
                        Colors.green,
                      ),
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: _buildStatusCard(
                        'Out',
                        '${provider.statusSummary!['out']}',
                        Colors.orange,
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 12),
                Row(
                  children: [
                    Expanded(
                      child: _buildStatusCard(
                        'Overdue',
                        '${provider.statusSummary!['overdue']}',
                        Colors.red,
                      ),
                    ),
                    const SizedBox(width: 12),
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
              const SizedBox(height: 80), // Space for FAB
            ],
          ),
        );
      },
    );
  }

  Widget _buildStatusCard(String title, String value, Color color) {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
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
            const SizedBox(height: 4),
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
