import 'package:flutter/foundation.dart';
import '../services/api_service.dart';

class GallonProvider with ChangeNotifier {
  final ApiService apiService;
  
  Map<String, dynamic>? _statusSummary;
  bool _isLoading = false;
  String? _error;

  GallonProvider(this.apiService);

  Map<String, dynamic>? get statusSummary => _statusSummary;
  bool get isLoading => _isLoading;
  String? get error => _error;

  Future<Map<String, dynamic>?> scanGallon(String gallonCode) async {
    _isLoading = true;
    _error = null;
    notifyListeners();

    try {
      final response = await apiService.scanGallon(gallonCode);
      _isLoading = false;
      notifyListeners();
      return response;
    } catch (e) {
      _error = e.toString().replaceAll('Exception: ', '');
      _isLoading = false;
      notifyListeners();
      return null;
    }
  }

  Future<bool> returnGallon(String gallonCode) async {
    _isLoading = true;
    _error = null;
    notifyListeners();

    try {
      await apiService.returnGallon(gallonCode);
      _isLoading = false;
      notifyListeners();
      
      // Refresh status summary
      await loadStatusSummary();
      
      return true;
    } catch (e) {
      _error = e.toString().replaceAll('Exception: ', '');
      _isLoading = false;
      notifyListeners();
      return false;
    }
  }

  Future<void> loadStatusSummary() async {
    try {
      _statusSummary = await apiService.getGallonStatusSummary();
      notifyListeners();
    } catch (e) {
      print('Error loading status summary: $e');
    }
  }

  void clearError() {
    _error = null;
    notifyListeners();
  }
}
