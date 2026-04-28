import 'package:flutter/material.dart';

import 'core/police_theme.dart';
import 'l10n/app_language_controller.dart';
import 'l10n/app_localizations.dart';
import 'pages/auth/login_page.dart';
import 'pages/home/dispatch_assignments_page.dart';
import 'services/notification_service.dart';

Future<void> main() async {
  WidgetsFlutterBinding.ensureInitialized();
  await NotificationService.initialize();
  final savedLocale = await AppLanguageController.loadSavedLocale();
  runApp(PoliceAssistantApp(initialLocale: savedLocale));
}

class PoliceAssistantApp extends StatefulWidget {
  const PoliceAssistantApp({super.key, this.initialLocale});

  final Locale? initialLocale;

  static PoliceAssistantAppState of(BuildContext context) {
    final state = context.findAncestorStateOfType<PoliceAssistantAppState>();
    assert(state != null, 'PoliceAssistantApp state not found in context.');
    return state!;
  }

  @override
  State<PoliceAssistantApp> createState() => PoliceAssistantAppState();
}

class PoliceAssistantAppState extends State<PoliceAssistantApp> {
  Locale? _locale;

  Locale? get currentLocale => _locale;

  @override
  void initState() {
    super.initState();
    _locale = widget.initialLocale;
  }

  Future<void> setLocale(Locale? locale) async {
    await AppLanguageController.saveLocale(locale);
    if (!mounted) return;
    setState(() {
      _locale = locale;
    });
  }

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      navigatorKey: appNavigatorKey,
      onGenerateTitle: (context) => AppLocalizations.of(context).appTitle,
      debugShowCheckedModeBanner: false,
      theme: PoliceTheme.theme,
      locale: _locale,
      supportedLocales: AppLocalizations.supportedLocales,
      localizationsDelegates: AppLocalizations.localizationsDelegates,
      builder: (context, child) {
        final l10n = AppLocalizations.of(context);
        return Directionality(
          textDirection: l10n.textDirection,
          child: child ?? const SizedBox.shrink(),
        );
      },
      localeResolutionCallback: (locale, supportedLocales) {
        if (_locale != null) {
          return _locale;
        }
        if (locale == null) {
          return const Locale('en');
        }
        for (final supported in supportedLocales) {
          if (supported.languageCode == locale.languageCode) {
            return supported;
          }
        }
        return const Locale('en');
      },
      routes: {
        '/dispatch-assignments': (context) {
          final id = ModalRoute.of(context)?.settings.arguments as int?;
          return DispatchAssignmentsPage(highlightId: id);
        },
      },
      home: const LoginPage(),
    );
  }
}
