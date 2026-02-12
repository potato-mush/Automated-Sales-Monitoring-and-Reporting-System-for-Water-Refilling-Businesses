import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:flutter/foundation.dart' show kIsWeb;
import 'package:provider/provider.dart';
import 'package:mobile_scanner/mobile_scanner.dart';
import '../providers/transaction_provider.dart';
import '../providers/gallon_provider.dart';

class NewTransactionScreen extends StatefulWidget {
  const NewTransactionScreen({super.key});

  @override
  _NewTransactionScreenState createState() => _NewTransactionScreenState();
}

class _NewTransactionScreenState extends State<NewTransactionScreen> {
  final _formKey = GlobalKey<FormState>();
  final _customerNameController = TextEditingController();
  final _customerPhoneController = TextEditingController();
  final _customerAddressController = TextEditingController();
  final _unitPriceController = TextEditingController(text: '25.00');
  final _notesController = TextEditingController();

  String _transactionType = 'walk-in';
  String _paymentMethod = 'cash';
  bool _showScanner = false;
  bool _showManualEntry = false;
  bool _isProcessingQR = false;
  bool _cameraReady = false;
  String _detectedQR = '';
  DateTime? _lastScanTime;
  final _manualGallonCodeController = TextEditingController();
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
    _customerNameController.dispose();
    _customerPhoneController.dispose();
    _customerAddressController.dispose();
    _unitPriceController.dispose();
    _notesController.dispose();
    _manualGallonCodeController.dispose();
    super.dispose();
  }

  Future<void> _scanGallon() async {
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
    await Future.delayed(Duration(milliseconds: 800));
    
    // Close scanner
    setState(() => _showScanner = false);
    
    // Extract gallon code from QR data
    final gallonCode = _extractGallonCode(rawValue);
    print('========================================');
    print('QR SCAN DEBUG:');
    print('Raw Value: "$rawValue"');
    print('Extracted Code: "$gallonCode"');
    print('Code Length: ${gallonCode.length}');
    print('========================================');
    
    final gallonProvider = Provider.of<GallonProvider>(context, listen: false);
    final result = await gallonProvider.scanGallon(gallonCode);
    
    setState(() {
      _isProcessingQR = false;
      _detectedQR = '';
      _cameraReady = false;
    });

    if (result != null && result['exists'] == true) {
      final gallon = result['gallon'];
      
      if (gallon['status'] == 'OUT') {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('Gallon ${gallon['gallon_code']} is already OUT'),
            backgroundColor: Colors.red,
          ),
        );
      } else {
        final transactionProvider = Provider.of<TransactionProvider>(context, listen: false);
        transactionProvider.addGallon(gallonCode);
        
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('Gallon ${gallon['gallon_code']} added'),
            backgroundColor: Colors.green,
          ),
        );
      }
    } else {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Gallon not found'),
          backgroundColor: Colors.red,
        ),
      );
    }
  }

  Future<void> _submitTransaction() async {
    if (_formKey.currentState!.validate()) {
      final transactionProvider = Provider.of<TransactionProvider>(context, listen: false);
      
      if (transactionProvider.scannedGallons.isEmpty) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('Please scan at least one gallon'),
            backgroundColor: Colors.red,
          ),
        );
        return;
      }

      final success = await transactionProvider.createTransaction(
        customerName: _customerNameController.text.trim(),
        customerPhone: _customerPhoneController.text.trim(),
        customerAddress: _customerAddressController.text.trim(),
        transactionType: _transactionType,
        paymentMethod: _paymentMethod,
        unitPrice: double.parse(_unitPriceController.text),
        notes: _notesController.text.trim(),
      );

      if (success && mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('Transaction created successfully'),
            backgroundColor: Colors.green,
          ),
        );
        Navigator.pop(context);
      } else if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(transactionProvider.error ?? 'Failed to create transaction'),
            backgroundColor: Colors.red,
          ),
        );
      }
    }
  }

  Future<void> _processManualGallonEntry(String gallonCode) async {
    if (gallonCode.isEmpty) return;

    final gallonProvider = Provider.of<GallonProvider>(context, listen: false);
    final result = await gallonProvider.scanGallon(gallonCode);

    if (result != null && result['exists'] == true) {
      final gallon = result['gallon'];
      
      if (gallon['status'] == 'OUT') {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('Gallon ${gallon['gallon_code']} is already OUT'),
            backgroundColor: Colors.red,
          ),
        );
      } else {
        final transactionProvider = Provider.of<TransactionProvider>(context, listen: false);
        transactionProvider.addGallon(gallonCode);
        
        setState(() {
          _showManualEntry = false;
          _manualGallonCodeController.clear();
        });
        
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('Gallon ${gallon['gallon_code']} added'),
            backgroundColor: Colors.green,
          ),
        );
      }
    } else {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Gallon not found'),
          backgroundColor: Colors.red,
        ),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('New Transaction'),
      ),
      body: _showScanner 
          ? _buildScanner() 
          : _showManualEntry 
              ? _buildManualEntry() 
              : _buildForm(),
      bottomNavigationBar: (_showScanner || _showManualEntry) ? null : _buildBottomBar(),
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
                  CircularProgressIndicator(color: Colors.white),
                  SizedBox(height: 16),
                  Text(
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
                    'Scan QR Code on Gallon Container',
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
            controller: _manualGallonCodeController,
            decoration: const InputDecoration(
              labelText: 'Gallon Code',
              hintText: 'e.g., GAL001',
              border: OutlineInputBorder(),
              prefixIcon: Icon(Icons.qr_code_2),
            ),
            textCapitalization: TextCapitalization.characters,
            autofocus: true,
            onSubmitted: (value) => _processManualGallonEntry(value.trim()),
          ),
          const SizedBox(height: 24),
          Row(
            children: [
              Expanded(
                child: OutlinedButton(
                  onPressed: () {
                    _manualGallonCodeController.clear();
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
                    final code = _manualGallonCodeController.text.trim();
                    await _processManualGallonEntry(code);
                  },
                  style: ElevatedButton.styleFrom(
                    padding: const EdgeInsets.symmetric(vertical: 16),
                  ),
                  child: Text('Add Gallon'),
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildForm() {
    return SingleChildScrollView(
      padding: const EdgeInsets.all(16),
      child: Form(
        key: _formKey,
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Scanned Gallons
            Consumer<TransactionProvider>(
              builder: (context, provider, _) {
                return Card(
                  child: Padding(
                    padding: const EdgeInsets.all(16),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              'Scanned Gallons (${provider.scannedGallons.length})',
                              style: const TextStyle(fontWeight: FontWeight.bold),
                            ),
                            const SizedBox(height: 8),
                            Row(
                              children: [
                                Expanded(
                                  child: ElevatedButton.icon(
                                    onPressed: _scanGallon,
                                    icon: const Icon(Icons.qr_code_scanner, size: 18),
                                    label: const Text('Scan'),
                                    style: ElevatedButton.styleFrom(
                                      padding: const EdgeInsets.symmetric(vertical: 12),
                                    ),
                                  ),
                                ),
                                const SizedBox(width: 8),
                                Expanded(
                                  child: OutlinedButton.icon(
                                    onPressed: () => setState(() => _showManualEntry = true),
                                    icon: const Icon(Icons.keyboard, size: 18),
                                    label: const Text('Manual'),
                                    style: OutlinedButton.styleFrom(
                                      padding: const EdgeInsets.symmetric(vertical: 12),
                                    ),
                                  ),
                                ),
                              ],
                            ),
                          ],
                        ),
                        const SizedBox(height: 8),
                        if (provider.scannedGallons.isEmpty)
                          const Text('No gallons scanned yet', style: TextStyle(color: Colors.grey)),
                        ...provider.scannedGallons.map((code) => ListTile(
                          leading: const Icon(Icons.water_drop, color: Colors.blue),
                          title: Text(code),
                          trailing: IconButton(
                            icon: const Icon(Icons.delete, color: Colors.red),
                            onPressed: () => provider.removeGallon(code),
                          ),
                        )),
                      ],
                    ),
                  ),
                );
              },
            ),
            const SizedBox(height: 16),

            // Customer Name
            TextFormField(
              controller: _customerNameController,
              decoration: const InputDecoration(
                labelText: 'Customer Name *',
                prefixIcon: Icon(Icons.person),
              ),
              validator: (value) {
                if (value == null || value.isEmpty) {
                  return 'Please enter customer name';
                }
                return null;
              },
            ),
            const SizedBox(height: 16),

            // Customer Phone
            TextFormField(
              controller: _customerPhoneController,
              keyboardType: TextInputType.phone,
              decoration: const InputDecoration(
                labelText: 'Customer Phone',
                prefixIcon: Icon(Icons.phone),
              ),
            ),
            const SizedBox(height: 16),

            // Transaction Type
            DropdownButtonFormField<String>(
              initialValue: _transactionType,
              decoration: const InputDecoration(
                labelText: 'Transaction Type *',
                prefixIcon: Icon(Icons.category),
              ),
              items: const [
                DropdownMenuItem(value: 'walk-in', child: Text('Walk-in')),
                DropdownMenuItem(value: 'delivery', child: Text('Delivery')),
                DropdownMenuItem(value: 'refill-only', child: Text('Refill Only')),
              ],
              onChanged: (value) {
                setState(() => _transactionType = value!);
              },
            ),
            const SizedBox(height: 16),

            // Customer Address (show only for delivery)
            if (_transactionType == 'delivery') ...[
              TextFormField(
                controller: _customerAddressController,
                maxLines: 2,
                decoration: const InputDecoration(
                  labelText: 'Delivery Address *',
                  prefixIcon: Icon(Icons.location_on),
                ),
                validator: (value) {
                  if (_transactionType == 'delivery' && (value == null || value.isEmpty)) {
                    return 'Please enter delivery address';
                  }
                  return null;
                },
              ),
              const SizedBox(height: 16),
            ],

            // Payment Method
            DropdownButtonFormField<String>(
              initialValue: _paymentMethod,
              decoration: const InputDecoration(
                labelText: 'Payment Method *',
                prefixIcon: Icon(Icons.payment),
              ),
              items: const [
                DropdownMenuItem(value: 'cash', child: Text('Cash')),
                DropdownMenuItem(value: 'gcash', child: Text('GCash')),
                DropdownMenuItem(value: 'card', child: Text('Card')),
                DropdownMenuItem(value: 'bank-transfer', child: Text('Bank Transfer')),
              ],
              onChanged: (value) {
                setState(() => _paymentMethod = value!);
              },
            ),
            const SizedBox(height: 16),

            // Unit Price
            TextFormField(
              controller: _unitPriceController,
              keyboardType: const TextInputType.numberWithOptions(decimal: true),
              decoration: const InputDecoration(
                labelText: 'Unit Price (₱) *',
                prefixIcon: Icon(Icons.attach_money),
              ),
              validator: (value) {
                if (value == null || value.isEmpty) {
                  return 'Please enter unit price';
                }
                if (double.tryParse(value) == null) {
                  return 'Please enter a valid price';
                }
                return null;
              },
            ),
            const SizedBox(height: 16),

            // Notes
            TextFormField(
              controller: _notesController,
              maxLines: 3,
              decoration: const InputDecoration(
                labelText: 'Notes',
                prefixIcon: Icon(Icons.note),
              ),
            ),
            const SizedBox(height: 16),

            // Total Display
            Consumer<TransactionProvider>(
              builder: (context, provider, _) {
                final quantity = provider.scannedGallons.length;
                final unitPrice = double.tryParse(_unitPriceController.text) ?? 0;
                final total = quantity * unitPrice;

                return Card(
                  color: Theme.of(context).primaryColor.withOpacity(0.1),
                  child: Padding(
                    padding: const EdgeInsets.all(16),
                    child: Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        const Text(
                          'Total Amount',
                          style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
                        ),
                        Text(
                          '₱${total.toStringAsFixed(2)}',
                          style: TextStyle(
                            fontSize: 24,
                            fontWeight: FontWeight.bold,
                            color: Theme.of(context).primaryColor,
                          ),
                        ),
                      ],
                    ),
                  ),
                );
              },
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildBottomBar() {
    return Consumer<TransactionProvider>(
      builder: (context, provider, _) {
        if (provider.isLoading) {
          return Container(
            padding: const EdgeInsets.all(16),
            child: const Center(child: CircularProgressIndicator()),
          );
        }

        return Container(
          padding: const EdgeInsets.all(16),
          child: ElevatedButton(
            onPressed: _submitTransaction,
            style: ElevatedButton.styleFrom(
              padding: const EdgeInsets.symmetric(vertical: 16),
            ),
            child: const Text(
              'CREATE TRANSACTION',
              style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
            ),
          ),
        );
      },
    );
  }
}
