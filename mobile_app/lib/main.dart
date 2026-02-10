import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:provider/provider.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'providers/auth_provider.dart';
import 'providers/transaction_provider.dart';
import 'providers/gallon_provider.dart';
import 'screens/login_screen.dart';
import 'screens/home_screen.dart';
import 'screens/new_transaction_screen.dart';
import 'screens/return_gallon_screen.dart';
import 'screens/inventory_screen.dart';
import 'screens/daily_summary_screen.dart';
import 'services/api_service.dart';

void main() async {
  WidgetsFlutterBinding.ensureInitialized();
  
  // Enable fullscreen mode - hide system navigation
  SystemChrome.setEnabledSystemUIMode(
    SystemUiMode.immersiveSticky,
    overlays: [],
  );
  
  // Lock to portrait mode
  SystemChrome.setPreferredOrientations([
    DeviceOrientation.portraitUp,
    DeviceOrientation.portraitDown,
  ]);
  
  final prefs = await SharedPreferences.getInstance();
  final apiService = ApiService(prefs);
  
  runApp(MyApp(apiService: apiService, prefs: prefs));
}

class MyApp extends StatelessWidget {
  final ApiService apiService;
  final SharedPreferences prefs;

  const MyApp({
    Key? key, 
    required this.apiService, 
    required this.prefs
  }) : super(key: key);

  @override
  Widget build(BuildContext context) {
    return MultiProvider(
      providers: [
        ChangeNotifierProvider(
          create: (_) => AuthProvider(apiService, prefs),
        ),
        ChangeNotifierProvider(
          create: (_) => TransactionProvider(apiService),
        ),
        ChangeNotifierProvider(
          create: (_) => GallonProvider(apiService),
        ),
      ],
      child: MaterialApp(
        title: 'Water Refilling System',
        debugShowCheckedModeBanner: false,
        theme: ThemeData(
          primarySwatch: Colors.blue,
          primaryColor: Color(0xFF1E88E5),
          colorScheme: ColorScheme.fromSeed(
            seedColor: Color(0xFF1E88E5),
            primary: Color(0xFF1E88E5),
            secondary: Color(0xFF26A69A),
          ),
          scaffoldBackgroundColor: Colors.grey[50],
          appBarTheme: AppBarTheme(
            backgroundColor: Color(0xFF1E88E5),
            foregroundColor: Colors.white,
            elevation: 0,
            centerTitle: true,
          ),
          cardTheme: CardThemeData(
            elevation: 2,
            shape: RoundedRectangleBorder(
              borderRadius: BorderRadius.circular(12),
            ),
          ),
          elevatedButtonTheme: ElevatedButtonThemeData(
            style: ElevatedButton.styleFrom(
              backgroundColor: Color(0xFF1E88E5),
              foregroundColor: Colors.white,
              padding: EdgeInsets.symmetric(horizontal: 24, vertical: 12),
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(8),
              ),
            ),
          ),
          inputDecorationTheme: InputDecorationTheme(
            border: OutlineInputBorder(
              borderRadius: BorderRadius.circular(8),
            ),
            filled: true,
            fillColor: Colors.white,
          ),
        ),
        home: Consumer<AuthProvider>(
          builder: (context, auth, _) {
            if (auth.isAuthenticated) {
              return HomeScreen();
            }
            return LoginScreen();
          },
        ),
        routes: {
          '/login': (context) => LoginScreen(),
          '/home': (context) => HomeScreen(),
          '/new-transaction': (context) => NewTransactionScreen(),
          '/return-gallon': (context) => ReturnGallonScreen(),
          '/inventory': (context) => InventoryScreen(),
          '/daily-summary': (context) => DailySummaryScreen(),
        },
      ),
    );
  }
}
