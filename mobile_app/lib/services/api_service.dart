import 'dart:convert';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';

class ApiService {
  // Change this to your backend URL
  static const String baseUrl = 'http://YOUR_IP_ADDRESS:8000/api';
  
  final SharedPreferences prefs;

  ApiService(this.prefs);

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
