import 'package:flutter/material.dart';

import 'core/police_theme.dart';
import 'pages/auth/login_page.dart';
import 'pages/home/dispatch_assignments_page.dart';
import 'services/notification_service.dart';

Future<void> main() async {
  WidgetsFlutterBinding.ensureInitialized();
  await NotificationService.initialize();
  runApp(const PoliceAssistantApp());
}

class PoliceAssistantApp extends StatelessWidget {
  const PoliceAssistantApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      navigatorKey: appNavigatorKey,
      title: 'Police Assistant',
      debugShowCheckedModeBanner: false,
      theme: PoliceTheme.theme,
      routes: {
        '/dispatch-assignments': (context) {
          final reportId = ModalRoute.of(context)?.settings.arguments as int?;
          return DispatchAssignmentsPage(highlightReportId: reportId);
        },
      },
      home: const LoginPage(),
    );
  }
}
