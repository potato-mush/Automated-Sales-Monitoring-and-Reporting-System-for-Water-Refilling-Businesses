import 'package:flutter/foundation.dart';
import '../services/api_service.dart';

class TransactionProvider with ChangeNotifier {
  final ApiService apiService;
  
  List<String> _scannedGallons = [];
  bool _isLoading = false;
  String? _error;
  Map<String, dynamic>? _todaySummary;

  TransactionProvider(this.apiService);

  List<String> get scannedGallons => _scannedGallons;
  bool get isLoading => _isLoading;
  String? get error => _error;
  Map<String, dynamic>? get todaySummary => _todaySummary;

  void addGallon(String gallonCode) {
    if (!_scannedGallons.contains(gallonCode)) {
      _scannedGallons.add(gallonCode);
      notifyListeners();
    }
  }

  void removeGallon(String gallonCode) {
    _scannedGallons.remove(gallonCode);
    notifyListeners();
  }

  void clearGallons() {
    _scannedGallons.clear();
    notifyListeners();
  }

  Future<bool> createTransaction({
    required String customerName,
    String? customerPhone,
    String? customerAddress,
    required String transactionType,
    required String paymentMethod,
    required double unitPrice,
    String? notes,
  }) async {
    if (_scannedGallons.isEmpty) {
      _error = 'Please scan at least one gallon';
      notifyListeners();
      return false;
    }

    _isLoading = true;
    _error = null;
    notifyListeners();

    try {
      final data = {
        'customer_name': customerName,
        'customer_phone': customerPhone,
        'customer_address': customerAddress,
        'transaction_type': transactionType,
        'payment_method': paymentMethod,
        'unit_price': unitPrice,
        'gallon_codes': _scannedGallons,
        'notes': notes,
      };

      await apiService.createTransaction(data);
      
      // Clear scanned gallons after successful transaction
      _scannedGallons.clear();
      
      _isLoading = false;
      notifyListeners();
      
      // Refresh today's summary
      await loadTodaySummary();
      
      return true;
    } catch (e) {
      _error = e.toString().replaceAll('Exception: ', '');
      _isLoading = false;
      notifyListeners();
      return false;
    }
  }

  Future<void> loadTodaySummary() async {
    try {
      _todaySummary = await apiService.getTodaySummary();
      notifyListeners();
    } catch (e) {
      print('Error loading today summary: $e');
    }
  }

  void clearError() {
    _error = null;
    notifyListeners();
  }
}
