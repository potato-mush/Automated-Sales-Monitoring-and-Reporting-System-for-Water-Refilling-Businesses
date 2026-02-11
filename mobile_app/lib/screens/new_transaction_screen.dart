import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:flutter/foundation.dart' show kIsWeb;
import 'package:provider/provider.dart';
import 'package:mobile_scanner/mobile_scanner.dart';
import '../providers/transaction_provider.dart';
import '../providers/gallon_provider.dart';

class NewTransactionScreen extends StatefulWidget {
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
  String _detectedQR = '';
  final _manualGallonCodeController = TextEditingController();
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
    _customerNameController.dispose();
    _customerPhoneController.dispose();
    _customerAddressController.dispose();
    _unitPriceController.dispose();
    _notesController.dispose();
    _manualGallonCodeController.dispose();
    super.dispose();
  }

  Future<void> _scanGallon() async {
    setState(() => _showScanner = true);
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
            
            ScaffoldMessenger.of(context).showSnackBar(
              SnackBar(
                content: Text('Gallon ${gallon['gallon_code']} added'),
                backgroundColor: Colors.green,
              ),
            );
          }
        } else {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text('Gallon not found'),
              backgroundColor: Colors.red,
            ),
          );
        }
        
        // Reset processing state to allow next scan
        await Future.delayed(Duration(milliseconds: 800));
        setState(() {
          _isProcessingQR = false;
          _detectedQR = '';
        });
      }
    }
  }

  Future<void> _submitTransaction() async {
    if (_formKey.currentState!.validate()) {
      final transactionProvider = Provider.of<TransactionProvider>(context, listen: false);
      
      if (transactionProvider.scannedGallons.isEmpty) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
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
          SnackBar(
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
        SnackBar(
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
        title: Text('New Transaction'),
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
                    'Scan QR Code on Gallon Container',
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
            controller: _manualGallonCodeController,
            decoration: InputDecoration(
              labelText: 'Gallon Code',
              hintText: 'e.g., GAL001',
              border: OutlineInputBorder(),
              prefixIcon: Icon(Icons.qr_code_2),
            ),
            textCapitalization: TextCapitalization.characters,
            autofocus: true,
            onSubmitted: (value) => _processManualGallonEntry(value.trim()),
          ),
          SizedBox(height: 24),
          Row(
            children: [
              Expanded(
                child: OutlinedButton(
                  onPressed: () {
                    _manualGallonCodeController.clear();
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
                    final code = _manualGallonCodeController.text.trim();
                    await _processManualGallonEntry(code);
                  },
                  child: Text('Add Gallon'),
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

  Widget _buildForm() {
    return SingleChildScrollView(
      padding: EdgeInsets.all(16),
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
                    padding: EdgeInsets.all(16),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              'Scanned Gallons (${provider.scannedGallons.length})',
                              style: TextStyle(fontWeight: FontWeight.bold),
                            ),
                            SizedBox(height: 8),
                            Row(
                              children: [
                                Expanded(
                                  child: ElevatedButton.icon(
                                    onPressed: _scanGallon,
                                    icon: Icon(Icons.qr_code_scanner, size: 18),
                                    label: Text('Scan'),
                                    style: ElevatedButton.styleFrom(
                                      padding: EdgeInsets.symmetric(vertical: 12),
                                    ),
                                  ),
                                ),
                                SizedBox(width: 8),
                                Expanded(
                                  child: OutlinedButton.icon(
                                    onPressed: () => setState(() => _showManualEntry = true),
                                    icon: Icon(Icons.keyboard, size: 18),
                                    label: Text('Manual'),
                                    style: OutlinedButton.styleFrom(
                                      padding: EdgeInsets.symmetric(vertical: 12),
                                    ),
                                  ),
                                ),
                              ],
                            ),
                          ],
                        ),
                        SizedBox(height: 8),
                        if (provider.scannedGallons.isEmpty)
                          Text('No gallons scanned yet', style: TextStyle(color: Colors.grey)),
                        ...provider.scannedGallons.map((code) => ListTile(
                          leading: Icon(Icons.water_drop, color: Colors.blue),
                          title: Text(code),
                          trailing: IconButton(
                            icon: Icon(Icons.delete, color: Colors.red),
                            onPressed: () => provider.removeGallon(code),
                          ),
                        )),
                      ],
                    ),
                  ),
                );
              },
            ),
            SizedBox(height: 16),

            // Customer Name
            TextFormField(
              controller: _customerNameController,
              decoration: InputDecoration(
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
            SizedBox(height: 16),

            // Customer Phone
            TextFormField(
              controller: _customerPhoneController,
              keyboardType: TextInputType.phone,
              decoration: InputDecoration(
                labelText: 'Customer Phone',
                prefixIcon: Icon(Icons.phone),
              ),
            ),
            SizedBox(height: 16),

            // Transaction Type
            DropdownButtonFormField<String>(
              value: _transactionType,
              decoration: InputDecoration(
                labelText: 'Transaction Type *',
                prefixIcon: Icon(Icons.category),
              ),
              items: [
                DropdownMenuItem(value: 'walk-in', child: Text('Walk-in')),
                DropdownMenuItem(value: 'delivery', child: Text('Delivery')),
                DropdownMenuItem(value: 'refill-only', child: Text('Refill Only')),
              ],
              onChanged: (value) {
                setState(() => _transactionType = value!);
              },
            ),
            SizedBox(height: 16),

            // Customer Address (show only for delivery)
            if (_transactionType == 'delivery') ...[
              TextFormField(
                controller: _customerAddressController,
                maxLines: 2,
                decoration: InputDecoration(
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
              SizedBox(height: 16),
            ],

            // Payment Method
            DropdownButtonFormField<String>(
              value: _paymentMethod,
              decoration: InputDecoration(
                labelText: 'Payment Method *',
                prefixIcon: Icon(Icons.payment),
              ),
              items: [
                DropdownMenuItem(value: 'cash', child: Text('Cash')),
                DropdownMenuItem(value: 'gcash', child: Text('GCash')),
                DropdownMenuItem(value: 'card', child: Text('Card')),
                DropdownMenuItem(value: 'bank-transfer', child: Text('Bank Transfer')),
              ],
              onChanged: (value) {
                setState(() => _paymentMethod = value!);
              },
            ),
            SizedBox(height: 16),

            // Unit Price
            TextFormField(
              controller: _unitPriceController,
              keyboardType: TextInputType.numberWithOptions(decimal: true),
              decoration: InputDecoration(
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
            SizedBox(height: 16),

            // Notes
            TextFormField(
              controller: _notesController,
              maxLines: 3,
              decoration: InputDecoration(
                labelText: 'Notes',
                prefixIcon: Icon(Icons.note),
              ),
            ),
            SizedBox(height: 16),

            // Total Display
            Consumer<TransactionProvider>(
              builder: (context, provider, _) {
                final quantity = provider.scannedGallons.length;
                final unitPrice = double.tryParse(_unitPriceController.text) ?? 0;
                final total = quantity * unitPrice;

                return Card(
                  color: Theme.of(context).primaryColor.withOpacity(0.1),
                  child: Padding(
                    padding: EdgeInsets.all(16),
                    child: Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Text(
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
            padding: EdgeInsets.all(16),
            child: Center(child: CircularProgressIndicator()),
          );
        }

        return Container(
          padding: EdgeInsets.all(16),
          child: ElevatedButton(
            onPressed: _submitTransaction,
            style: ElevatedButton.styleFrom(
              padding: EdgeInsets.symmetric(vertical: 16),
            ),
            child: Text(
              'CREATE TRANSACTION',
              style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
            ),
          ),
        );
      },
    );
  }
}
