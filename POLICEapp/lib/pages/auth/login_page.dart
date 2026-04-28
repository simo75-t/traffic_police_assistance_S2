import 'package:flutter/material.dart';

import '../../core/police_theme.dart';
import '../../l10n/app_localizations.dart';
import '../../services/api_service.dart';
import '../../services/notification_service.dart';
import '../../services/officer_presence_service.dart';
import '../../services/secure_storage.dart';
import '../../widgets/app_button.dart';
import '../../widgets/app_card.dart';
import '../../widgets/section_header.dart';
import '../home/home_page.dart';

class LoginPage extends StatefulWidget {
  const LoginPage({super.key});

  @override
  State<LoginPage> createState() => _LoginPageState();
}

class _LoginPageState extends State<LoginPage> {
  final _formKey = GlobalKey<FormState>();
  final emailController = TextEditingController();
  final passwordController = TextEditingController();

  bool _loading = false;

  @override
  void dispose() {
    emailController.dispose();
    passwordController.dispose();
    super.dispose();
  }

  void _showError(String message) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text(message)),
    );
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;

    setState(() => _loading = true);

    final l10n = AppLocalizations.of(context);
    final email = emailController.text.trim();
    final password = passwordController.text;

    try {
      final res = await ApiService.login(email, password);
      if (!mounted) return;

      final token = ApiService.extractLoginToken(res);
      final tokenType = ApiService.extractLoginTokenType(res);

      if (token == null || token.isEmpty) {
        _showError(l10n.loginErrorNoToken);
        return;
      }

      await SecureStorage.saveAuthSession(token: token, tokenType: tokenType);
      try {
        await NotificationService.syncTokenWithBackend();
      } catch (e) {
        debugPrint('FCM sync skipped after login: $e');
      }

      try {
        await OfficerPresenceService.start();
      } catch (e) {
        debugPrint('Presence sync skipped after login: $e');
      }

      if (!mounted) return;
      Navigator.of(context).pushReplacement(
        MaterialPageRoute(builder: (_) => const HomePage()),
      );
    } catch (e) {
      if (mounted) {
        _showError(l10n.loginErrorGeneric('$e'));
      }
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    final isRtl = l10n.isRtl;

    return Scaffold(
      body: Container(
        decoration: const BoxDecoration(
          gradient: LinearGradient(
            colors: [Color(0xFFF6F9FD), Color(0xFFE6EEF7)],
            begin: Alignment.topCenter,
            end: Alignment.bottomCenter,
          ),
        ),
        child: Center(
          child: SingleChildScrollView(
            padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 32),
            child: ConstrainedBox(
              constraints: const BoxConstraints(maxWidth: 460),
              child: Column(
                children: [
                  Container(
                    width: 88,
                    height: 88,
                    decoration: BoxDecoration(
                      color: PoliceTheme.primary,
                      borderRadius: BorderRadius.circular(28),
                      boxShadow: const [
                        BoxShadow(
                          color: Color(0x290B1E3A),
                          blurRadius: 32,
                          offset: Offset(0, 14),
                        ),
                      ],
                    ),
                    child: const Icon(
                      Icons.shield_outlined,
                      size: 42,
                      color: Colors.white,
                    ),
                  ),
                  const SizedBox(height: 20),
                  Text(
                    l10n.loginTitle,
                    style: Theme.of(context).textTheme.headlineMedium,
                    textAlign: TextAlign.center,
                  ),
                  const SizedBox(height: 8),
                  Text(
                    l10n.loginSubtitle,
                    style: Theme.of(context).textTheme.bodyMedium,
                    textAlign: TextAlign.center,
                  ),
                  const SizedBox(height: 24),
                  AppCard(
                    padding: const EdgeInsets.all(20),
                    child: Form(
                      key: _formKey,
                      autovalidateMode: AutovalidateMode.onUserInteraction,
                      child: Column(
                        children: [
                          SectionHeader(
                            title: l10n.loginSectionTitle,
                            subtitle: l10n.loginSectionSubtitle,
                          ),
                          const SizedBox(height: 16),
                          TextFormField(
                            controller: emailController,
                            keyboardType: TextInputType.emailAddress,
                            textAlign:
                                isRtl ? TextAlign.right : TextAlign.left,
                            textDirection: l10n.textDirection,
                            decoration: InputDecoration(
                              labelText: l10n.loginEmail,
                              prefixIcon: const Icon(Icons.badge_outlined),
                            ),
                            validator: (value) {
                              if (value == null || value.isEmpty) {
                                return l10n.loginValidationEmailRequired;
                              }
                              final emailReg = RegExp(r'^[^@]+@[^@]+\.[^@]+$');
                              if (!emailReg.hasMatch(value)) {
                                return l10n.loginValidationEmailInvalid;
                              }
                              return null;
                            },
                          ),
                          const SizedBox(height: 16),
                          TextFormField(
                            controller: passwordController,
                            obscureText: true,
                            textAlign:
                                isRtl ? TextAlign.right : TextAlign.left,
                            textDirection: l10n.textDirection,
                            decoration: InputDecoration(
                              labelText: l10n.loginPassword,
                              prefixIcon: const Icon(Icons.lock_outline),
                            ),
                            validator: (value) {
                              if (value == null || value.isEmpty) {
                                return l10n.loginValidationPasswordRequired;
                              }
                              if (value.length < 8) {
                                return l10n.loginValidationPasswordShort;
                              }
                              return null;
                            },
                          ),
                          const SizedBox(height: 20),
                          AppButton(
                            label: l10n.loginSubmit,
                            onPressed: _submit,
                            loading: _loading,
                            icon: Icons.login,
                          ),
                        ],
                      ),
                    ),
                  ),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }
}
