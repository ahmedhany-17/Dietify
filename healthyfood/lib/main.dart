import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:healthyfood/splash/splash.dart';
import 'package:healthyfood/providers/app_state.dart';

Future<void> main() async {
  runApp(
    ChangeNotifierProvider(
      create: (context) => AppState(),
      child: const MyApp(),
    ),
  );
}

class MyApp extends StatelessWidget {
  const MyApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      debugShowCheckedModeBanner: false,
      home: SplashView(),
    );
  }
}
