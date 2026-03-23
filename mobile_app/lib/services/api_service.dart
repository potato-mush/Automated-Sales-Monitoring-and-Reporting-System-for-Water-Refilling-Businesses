import 'dart:convert';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';

class ApiService {
  // Default fallback URL
  static const String defaultBaseUrl = 'http://192.168.1.100:8000/api';
  
  final SharedPreferences prefs;

  ApiService(this.prefs);

  // Get the current base URL (either custom or default)
  String get baseUrl {
    return prefs.getString('api_base_url') ?? defaultBaseUrl;
  }

  // Allow users to set custom API URL
  Future<void> setBaseUrl(String url) async {
    await prefs.setString('api_base_url', url);
  }

  // Test if the URL is reachable
  Future<Map<String, dynamic>> testConnection(String url) async {
    try {
      final testUrl = url.endsWith('/api') ? url : '$url/api';
      final fullUrl = '$testUrl/test';
      
      print('Testing connection to: $fullUrl');
      
      final response = await http.get(
        Uri.parse(fullUrl),
        headers: {'Accept': 'application/json'},
      ).timeout(const Duration(seconds: 10));
      
      print('Response status: ${response.statusCode}');
      print('Response body: ${response.body}');
      
      if (response.statusCode == 200) {
        return {'success': true, 'message': 'Connection successful'};
      } else {
        return {
          'success': false,
          'message': 'Server returned status ${response.statusCode}'
        };
      }
    } catch (e) {
      print('Connection error: $e');
      return {
        'success': false,
        'message': 'Connection failed: ${e.toString()}'
      };
    }
  }

  Future<String?> getToken() async {
    return prefs.getString('token');
  }

  Future<void> saveToken(String token) async {
    await prefs.setString('token', token);
  }

  Future<void> removeToken() async {
    await prefs.remove('token');
  }

  Map<String, String> _getHeaders({bool needsAuth = true}) {
    final headers = {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    };

    if (needsAuth) {
      final token = prefs.getString('token');
      if (token != null) {
        headers['Authorization'] = 'Bearer $token';
      }
    }

    return headers;
  }

  // Auth APIs
  Future<Map<String, dynamic>> login(String email, String password) async {
    final response = await http.post(
      Uri.parse('$baseUrl/login'),
      headers: _getHeaders(needsAuth: false),
      body: jsonEncode({'email': email, 'password': password}),
    );

    if (response.statusCode == 200) {
      final data = jsonDecode(response.body);
      await saveToken(data['token']);
      return data;
    } else {
      throw Exception(jsonDecode(response.body)['message'] ?? 'Login failed');
    }
  }

  Future<void> logout() async {
    try {
      await http.post(
        Uri.parse('$baseUrl/logout'),
        headers: _getHeaders(),
      );
    } finally {
      await removeToken();
    }
  }

  Future<Map<String, dynamic>> getMe() async {
    final response = await http.get(
      Uri.parse('$baseUrl/me'),
      headers: _getHeaders(),
    );

    if (response.statusCode == 200) {
      return jsonDecode(response.body);
    } else {
      throw Exception('Failed to get user data');
    }
  }

  // Transaction APIs
  Future<Map<String, dynamic>> getTodaySummary() async {
    final response = await http.get(
      Uri.parse('$baseUrl/transactions/today-summary'),
      headers: _getHeaders(),
    );

    if (response.statusCode == 200) {
      return jsonDecode(response.body);
    } else {
      throw Exception('Failed to get today summary');
    }
  }

  Future<List<dynamic>> getTransactions({int page = 1, String? type, String? date}) async {
    var url = '$baseUrl/transactions?page=$page';
    if (type != null) url += '&type=$type';
    if (date != null) url += '&date=$date';

    final response = await http.get(
      Uri.parse(url),
      headers: _getHeaders(),
    );

    if (response.statusCode == 200) {
      final data = jsonDecode(response.body);
      return data['data'];
    } else {
      throw Exception('Failed to get transactions');
    }
  }

  Future<Map<String, dynamic>> createTransaction(Map<String, dynamic> data) async {
    final response = await http.post(
      Uri.parse('$baseUrl/transactions'),
      headers: _getHeaders(),
      body: jsonEncode(data),
    );

    if (response.statusCode == 201) {
      return jsonDecode(response.body);
    } else {
      final error = jsonDecode(response.body);
      throw Exception(error['message'] ?? 'Failed to create transaction');
    }
  }

  // Gallon APIs
  Future<Map<String, dynamic>> scanGallon(String gallonCode) async {
    print('API Service - Scanning gallon: "$gallonCode"');
    
    final response = await http.post(
      Uri.parse('$baseUrl/gallons/scan'),
      headers: _getHeaders(),
      body: jsonEncode({'gallon_code': gallonCode}),
    );

    print('API Response - Status: ${response.statusCode}');
    print('API Response - Body: ${response.body}');

    if (response.statusCode == 200) {
      return jsonDecode(response.body);
    } else if (response.statusCode == 404) {
      return {'exists': false};
    } else {
      throw Exception('Failed to scan gallon');
    }
  }

  Future<Map<String, dynamic>> returnGallon(String gallonCode) async {
    final response = await http.post(
      Uri.parse('$baseUrl/gallons/return'),
      headers: _getHeaders(),
      body: jsonEncode({'gallon_code': gallonCode}),
    );

    if (response.statusCode == 200) {
      return jsonDecode(response.body);
    } else {
      final error = jsonDecode(response.body);
      throw Exception(error['message'] ?? 'Failed to return gallon');
    }
  }

  Future<Map<String, dynamic>> getGallonStatusSummary() async {
    final response = await http.get(
      Uri.parse('$baseUrl/gallons/status-summary'),
      headers: _getHeaders(),
    );

    if (response.statusCode == 200) {
      return jsonDecode(response.body);
    } else {
      throw Exception('Failed to get gallon status');
    }
  }

  Future<List<dynamic>> getGallons({String? status, int page = 1}) async {
    var url = '$baseUrl/gallons?page=$page';
    if (status != null) url += '&status=$status';

    final response = await http.get(
      Uri.parse(url),
      headers: _getHeaders(),
    );

    if (response.statusCode == 200) {
      final data = jsonDecode(response.body);
      return data['data'];
    } else {
      throw Exception('Failed to get gallons');
    }
  }

  // Dashboard APIs
  Future<Map<String, dynamic>> getDashboard() async {
    final response = await http.get(
      Uri.parse('$baseUrl/dashboard'),
      headers: _getHeaders(),
    );

    if (response.statusCode == 200) {
      return jsonDecode(response.body);
    } else {
      throw Exception('Failed to get dashboard data');
    }
  }

  // Inventory APIs
  Future<List<dynamic>> getInventoryItems({
    String? category,
    bool lowStockOnly = false,
    String? search,
  }) async {
    final query = <String>[];

    if (category != null && category.isNotEmpty && category != 'all') {
      query.add('category=$category');
    }
    if (lowStockOnly) {
      query.add('low_stock=1');
    }
    if (search != null && search.trim().isNotEmpty) {
      query.add('search=${Uri.encodeQueryComponent(search.trim())}');
    }

    final queryString = query.isEmpty ? '' : '?${query.join('&')}';

    final response = await http.get(
      Uri.parse('$baseUrl/inventory$queryString'),
      headers: _getHeaders(),
    );

    if (response.statusCode == 200) {
      return jsonDecode(response.body) as List<dynamic>;
    }

    throw Exception('Failed to load inventory items');
  }

  Future<Map<String, dynamic>> getInventoryStatistics() async {
    final response = await http.get(
      Uri.parse('$baseUrl/inventory/statistics'),
      headers: _getHeaders(),
    );

    if (response.statusCode == 200) {
      return jsonDecode(response.body) as Map<String, dynamic>;
    }

    throw Exception('Failed to load inventory statistics');
  }

  Future<Map<String, dynamic>> createInventoryItem(Map<String, dynamic> data) async {
    final response = await http.post(
      Uri.parse('$baseUrl/inventory'),
      headers: _getHeaders(),
      body: jsonEncode(data),
    );

    if (response.statusCode == 201) {
      return jsonDecode(response.body) as Map<String, dynamic>;
    }

    final error = jsonDecode(response.body);
    throw Exception(error['message'] ?? 'Failed to create inventory item');
  }

  Future<Map<String, dynamic>> updateInventoryItem(int id, Map<String, dynamic> data) async {
    final response = await http.put(
      Uri.parse('$baseUrl/inventory/$id'),
      headers: _getHeaders(),
      body: jsonEncode(data),
    );

    if (response.statusCode == 200) {
      return jsonDecode(response.body) as Map<String, dynamic>;
    }

    final error = jsonDecode(response.body);
    throw Exception(error['message'] ?? 'Failed to update inventory item');
  }

  Future<void> deleteInventoryItem(int id) async {
    final response = await http.delete(
      Uri.parse('$baseUrl/inventory/$id'),
      headers: _getHeaders(),
    );

    if (response.statusCode != 200) {
      final error = jsonDecode(response.body);
      throw Exception(error['message'] ?? 'Failed to delete inventory item');
    }
  }

  Future<Map<String, dynamic>> adjustInventoryQuantity(
    int id,
    int adjustment, {
    String? reason,
  }) async {
    final response = await http.post(
      Uri.parse('$baseUrl/inventory/$id/adjust'),
      headers: _getHeaders(),
      body: jsonEncode({
        'adjustment': adjustment,
        'reason': reason,
      }),
    );

    if (response.statusCode == 200) {
      return jsonDecode(response.body) as Map<String, dynamic>;
    }

    final error = jsonDecode(response.body);
    throw Exception(error['message'] ?? 'Failed to adjust inventory quantity');
  }

  // Settings APIs
  Future<Map<String, dynamic>> getSettings() async {
    final response = await http.get(
      Uri.parse('$baseUrl/settings'),
      headers: _getHeaders(),
    );

    if (response.statusCode == 200) {
      return jsonDecode(response.body);
    } else {
      throw Exception('Failed to get settings');
    }
  }
}
