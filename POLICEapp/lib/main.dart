import 'package:flutter/material.dart';
import 'core/police_theme.dart';
import 'pages/auth/login_page.dart';

void main() {
  runApp(const PoliceAssistantApp());
}

class PoliceAssistantApp extends StatelessWidget {
  const PoliceAssistantApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'Police Assistant',
      debugShowCheckedModeBanner: false,
      theme: PoliceTheme.theme,
      home: const LoginPage(),
    );
  }
}
