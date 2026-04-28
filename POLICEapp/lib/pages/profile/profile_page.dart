import 'package:flutter/material.dart';
import 'package:intl/intl.dart' show DateFormat;

import '../../core/police_theme.dart';
import '../../l10n/app_localizations.dart';
import '../../main.dart';
import '../../models/profile.dart';
import '../../services/api_service.dart';
import '../../services/officer_presence_service.dart';
import '../../services/secure_storage.dart';
import '../../widgets/app_button.dart';
import '../../widgets/app_card.dart';
import '../../widgets/empty_state_widget.dart';
import '../../widgets/loading_widget.dart';
import '../../widgets/section_header.dart';
import '../auth/login_page.dart';

class ProfilePage extends StatefulWidget {
  const ProfilePage({super.key, required this.token});

  final String token;

  @override
  State<ProfilePage> createState() => _ProfilePageState();
}

class _ProfilePageState extends State<ProfilePage> {
  final _formKey = GlobalKey<FormState>();
  final _nameController = TextEditingController();
  final _emailController = TextEditingController();
  final _phoneController = TextEditingController();

  late Future<Profile> _profileFuture;
  bool _saving = false;
  String _languageSelection = 'system';

  @override
  void initState() {
    super.initState();
    _profileFuture = _loadProfile();
  }

  void _syncLanguageSelection() {
    final appState = PoliceAssistantApp.of(context);
    final locale = appState.currentLocale;
    _languageSelection = locale?.languageCode ?? 'system';
  }

  Future<Profile> _loadProfile() async {
    final profile = await ApiService.getProfile(widget.token);
    _nameController.text = profile.name;
    _emailController.text = profile.email;
    _phoneController.text = profile.phone ?? '';
    return profile;
  }

  Future<void> _saveProfile() async {
    if (!_formKey.currentState!.validate()) return;

    setState(() => _saving = true);

    try {
      final updated = await ApiService.updateProfile(
        widget.token,
        name: _nameController.text,
        email: _emailController.text,
        phone: _phoneController.text,
      );

      _nameController.text = updated.name;
      _emailController.text = updated.email;
      _phoneController.text = updated.phone ?? '';

      setState(() {
        _profileFuture = Future.value(updated);
      });

      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(AppLocalizations.of(context).profileUpdatedSuccess)),
      );
    } catch (e) {
      if (!mounted) return;
      final l10n = AppLocalizations.of(context);
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(l10n.profileUpdatedError(e.toString()))),
      );
    } finally {
      if (mounted) {
        setState(() => _saving = false);
      }
    }
  }

  Future<void> _changeLanguage(String? value) async {
    if (value == null) return;

    final appState = PoliceAssistantApp.of(context);
    final locale = value == 'system' ? null : Locale(value);
    await appState.setLocale(locale);

    if (!mounted) return;
    setState(() {
      _languageSelection = value;
    });
  }

  Future<void> _logout() async {
    try {
      await ApiService.logout(widget.token);
    } catch (_) {}

    await OfficerPresenceService.stop();
    await SecureStorage.deleteToken();
    if (!mounted) return;

    Navigator.of(context).pushAndRemoveUntil(
      MaterialPageRoute(builder: (_) => const LoginPage()),
      (route) => false,
    );
  }

  String _formatLastSeen(AppLocalizations l10n, String? rawValue) {
    if (rawValue == null || rawValue.trim().isEmpty) {
      return l10n.notAvailable;
    }

    final parsed = DateTime.tryParse(rawValue);
    if (parsed == null) {
      return rawValue;
    }

    return DateFormat('yyyy/MM/dd - HH:mm').format(parsed.toLocal());
  }

  @override
  void didChangeDependencies() {
    super.didChangeDependencies();
    _syncLanguageSelection();
  }

  @override
  void dispose() {
    _nameController.dispose();
    _emailController.dispose();
    _phoneController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    final isRtl = l10n.isRtl;

    return Scaffold(
      appBar: AppBar(title: Text(l10n.profilePageTitle)),
      body: FutureBuilder<Profile>(
        future: _profileFuture,
        builder: (context, snapshot) {
          if (snapshot.connectionState == ConnectionState.waiting) {
            return LoadingWidget(label: l10n.profileLoading);
          }

          if (snapshot.hasError) {
            return Padding(
              padding: const EdgeInsets.all(16),
              child: EmptyStateWidget(
                title: l10n.profileErrorTitle,
                subtitle: l10n.profileErrorSubtitle(snapshot.error.toString()),
                icon: Icons.error_outline,
                actionLabel: l10n.retry,
                onAction: () {
                  setState(() {
                    _profileFuture = _loadProfile();
                  });
                },
              ),
            );
          }

          final profile = snapshot.data!;

          return SingleChildScrollView(
            padding: const EdgeInsets.all(16),
            child: Form(
              key: _formKey,
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  AppCard(
                    padding: EdgeInsets.zero,
                    backgroundColor: PoliceTheme.primary,
                    borderColor: PoliceTheme.primary,
                    child: Container(
                      padding: const EdgeInsets.all(20),
                      decoration: BoxDecoration(
                        borderRadius: BorderRadius.circular(24),
                        gradient: const LinearGradient(
                          colors: [PoliceTheme.primary, PoliceTheme.secondary],
                          begin: Alignment.topRight,
                          end: Alignment.bottomLeft,
                        ),
                      ),
                      child: Column(
                        children: [
                          CircleAvatar(
                            radius: 34,
                            backgroundColor: Colors.white24,
                            backgroundImage: profile.profileImage != null &&
                                    profile.profileImage!.trim().isNotEmpty
                                ? NetworkImage(profile.profileImage!)
                                : null,
                            child: profile.profileImage == null ||
                                    profile.profileImage!.trim().isEmpty
                                ? const Icon(
                                    Icons.person_outline,
                                    size: 34,
                                    color: Colors.white,
                                  )
                                : null,
                          ),
                          const SizedBox(height: 12),
                          Text(
                            profile.name.isEmpty
                                ? l10n.profileUnknownUser
                                : profile.name,
                            style: const TextStyle(
                              color: Colors.white,
                              fontSize: 22,
                              fontWeight: FontWeight.bold,
                            ),
                            textAlign: TextAlign.center,
                          ),
                          const SizedBox(height: 6),
                          Text(
                            l10n.roleLabel(profile.role),
                            style: const TextStyle(color: Colors.white70),
                            textAlign: TextAlign.center,
                          ),
                          const SizedBox(height: 12),
                          Wrap(
                            spacing: 8,
                            runSpacing: 8,
                            alignment: WrapAlignment.center,
                            children: [
                              _InfoChip(
                                icon: Icons.verified_user_outlined,
                                label: profile.isActive
                                    ? l10n.profileAccountActive
                                    : l10n.profileAccountInactive,
                              ),
                              _InfoChip(
                                icon: Icons.phone_outlined,
                                label: profile.phone?.trim().isNotEmpty == true
                                    ? profile.phone!
                                    : l10n.profileNoPhone,
                              ),
                            ],
                          ),
                        ],
                      ),
                    ),
                  ),
                  const SizedBox(height: 16),
                  AppCard(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        SectionHeader(
                          title: l10n.profileLanguageTitle,
                          subtitle: l10n.profileLanguageSubtitle,
                        ),
                        const SizedBox(height: 16),
                        DropdownButtonFormField<String>(
                          value: _languageSelection,
                          decoration: InputDecoration(
                            labelText: l10n.language,
                            prefixIcon: const Icon(Icons.language_outlined),
                          ),
                          items: [
                            DropdownMenuItem(
                              value: 'system',
                              child: Text(l10n.languageSystem),
                            ),
                            DropdownMenuItem(
                              value: 'ar',
                              child: Text(l10n.languageArabic),
                            ),
                            DropdownMenuItem(
                              value: 'en',
                              child: Text(l10n.languageEnglish),
                            ),
                          ],
                          onChanged: _changeLanguage,
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 16),
                  AppCard(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        SectionHeader(
                          title: l10n.profileAccountInfoTitle,
                          subtitle: l10n.profileAccountInfoSubtitle,
                        ),
                        const SizedBox(height: 16),
                        _ProfileMetaRow(
                          icon: Icons.badge_outlined,
                          label: l10n.profileRole,
                          value: l10n.roleLabel(profile.role),
                        ),
                        _ProfileMetaRow(
                          icon: Icons.email_outlined,
                          label: l10n.profileCurrentEmail,
                          value: profile.email,
                        ),
                        _ProfileMetaRow(
                          icon: Icons.access_time_outlined,
                          label: l10n.profileLastSeen,
                          value: _formatLastSeen(l10n, profile.lastSeenAt),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 16),
                  AppCard(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        SectionHeader(
                          title: l10n.profileEditTitle,
                          subtitle: l10n.profileEditSubtitle,
                        ),
                        const SizedBox(height: 16),
                        TextFormField(
                          controller: _nameController,
                          textAlign: isRtl ? TextAlign.right : TextAlign.left,
                          textDirection: l10n.textDirection,
                          decoration: InputDecoration(
                            labelText: l10n.profileFullName,
                            prefixIcon: const Icon(Icons.person_outline),
                          ),
                          validator: (value) {
                            if (value == null || value.trim().length < 3) {
                              return l10n.profileValidationName;
                            }
                            return null;
                          },
                        ),
                        const SizedBox(height: 16),
                        TextFormField(
                          controller: _emailController,
                          keyboardType: TextInputType.emailAddress,
                          textAlign: isRtl ? TextAlign.right : TextAlign.left,
                          textDirection: l10n.textDirection,
                          decoration: InputDecoration(
                            labelText: l10n.profileEmail,
                            prefixIcon: const Icon(Icons.email_outlined),
                          ),
                          validator: (value) {
                            final text = value?.trim() ?? '';
                            if (text.isEmpty || !text.contains('@')) {
                              return l10n.profileValidationEmail;
                            }
                            return null;
                          },
                        ),
                        const SizedBox(height: 16),
                        TextFormField(
                          controller: _phoneController,
                          keyboardType: TextInputType.phone,
                          textAlign: isRtl ? TextAlign.right : TextAlign.left,
                          textDirection: l10n.textDirection,
                          decoration: InputDecoration(
                            labelText: l10n.profilePhone,
                            prefixIcon: const Icon(Icons.phone_outlined),
                          ),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 16),
                  AppButton(
                    label: l10n.profileSaveChanges,
                    onPressed: _saving ? null : _saveProfile,
                    loading: _saving,
                    icon: Icons.save_outlined,
                  ),
                  const SizedBox(height: 12),
                  AppButton(
                    label: l10n.profileLogout,
                    onPressed: _logout,
                    icon: Icons.logout_outlined,
                    variant: AppButtonVariant.secondary,
                  ),
                ],
              ),
            ),
          );
        },
      ),
    );
  }
}

class _ProfileMetaRow extends StatelessWidget {
  const _ProfileMetaRow({
    required this.icon,
    required this.label,
    required this.value,
  });

  final IconData icon;
  final String label;
  final String value;

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);

    return Padding(
      padding: const EdgeInsets.only(bottom: 12),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Container(
            width: 38,
            height: 38,
            decoration: BoxDecoration(
              color: PoliceTheme.secondary.withValues(alpha: 0.10),
              borderRadius: BorderRadius.circular(14),
            ),
            child: Icon(icon, color: PoliceTheme.secondary, size: 20),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  label,
                  style: Theme.of(context).textTheme.bodySmall,
                  textAlign: l10n.startTextAlign,
                ),
                const SizedBox(height: 4),
                Text(
                  value.isEmpty ? l10n.notAvailable : value,
                  style: Theme.of(context).textTheme.bodyLarge?.copyWith(
                        color: PoliceTheme.textPrimary,
                      ),
                  textAlign: l10n.startTextAlign,
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _InfoChip extends StatelessWidget {
  const _InfoChip({
    required this.icon,
    required this.label,
  });

  final IconData icon;
  final String label;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
      decoration: BoxDecoration(
        color: Colors.white.withValues(alpha: 0.14),
        borderRadius: BorderRadius.circular(999),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(icon, size: 16, color: Colors.white),
          const SizedBox(width: 6),
          Flexible(
            child: Text(
              label,
              style: const TextStyle(color: Colors.white),
              textAlign: TextAlign.center,
            ),
          ),
        ],
      ),
    );
  }
}
