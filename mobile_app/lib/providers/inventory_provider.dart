import 'package:flutter/foundation.dart';
import '../services/api_service.dart';

class InventoryProvider with ChangeNotifier {
  final ApiService apiService;

  List<Map<String, dynamic>> _items = [];
  Map<String, dynamic>? _statistics;
  bool _isLoading = false;
  String? _error;

  InventoryProvider(this.apiService);

  List<Map<String, dynamic>> get items => _items;
  Map<String, dynamic>? get statistics => _statistics;
  bool get isLoading => _isLoading;
  String? get error => _error;

  int get lowStockCount => _statistics?['low_stock_items'] ?? 0;

  int _asInt(dynamic value) {
    if (value is int) return value;
    if (value is double) return value.toInt();
    return int.tryParse(value?.toString() ?? '0') ?? 0;
  }

  List<Map<String, dynamic>> get lowStockItems {
    return _items.where((item) {
      final quantity = _asInt(item['quantity']);
      final reorderLevel = _asInt(item['reorder_level']);
      return quantity <= reorderLevel;
    }).toList();
  }

  Future<void> refreshData({
    String? category,
    bool lowStockOnly = false,
    String? search,
  }) async {
    _isLoading = true;
    _error = null;
    notifyListeners();

    try {
      final results = await Future.wait([
        apiService.getInventoryItems(
          category: category,
          lowStockOnly: lowStockOnly,
          search: search,
        ),
        apiService.getInventoryStatistics(),
      ]);

      _items = (results[0] as List<dynamic>)
          .cast<Map<String, dynamic>>();
      _statistics = results[1] as Map<String, dynamic>;
    } catch (e) {
      _error = e.toString().replaceAll('Exception: ', '');
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }

  Future<bool> createItem(Map<String, dynamic> data) async {
    try {
      await apiService.createInventoryItem(data);
      return true;
    } catch (e) {
      _error = e.toString().replaceAll('Exception: ', '');
      notifyListeners();
      return false;
    }
  }

  Future<bool> updateItem(int id, Map<String, dynamic> data) async {
    try {
      await apiService.updateInventoryItem(id, data);
      return true;
    } catch (e) {
      _error = e.toString().replaceAll('Exception: ', '');
      notifyListeners();
      return false;
    }
  }

  Future<bool> deleteItem(int id) async {
    try {
      await apiService.deleteInventoryItem(id);
      return true;
    } catch (e) {
      _error = e.toString().replaceAll('Exception: ', '');
      notifyListeners();
      return false;
    }
  }

  Future<bool> adjustQuantity(int id, int adjustment, {String? reason}) async {
    try {
      await apiService.adjustInventoryQuantity(id, adjustment, reason: reason);
      return true;
    } catch (e) {
      _error = e.toString().replaceAll('Exception: ', '');
      notifyListeners();
      return false;
    }
  }

  void clearError() {
    _error = null;
    notifyListeners();
  }
}
