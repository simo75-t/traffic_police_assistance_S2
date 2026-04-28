import 'package:flutter/material.dart';

import '../l10n/app_localizations.dart';
import '../services/secure_storage.dart';
import '../pages/home/add_fine_page.dart';
import '../pages/home/dispatch_assignments_page.dart';
import '../pages/home/home_page.dart';
import '../pages/home/my_violation.dart';
import '../pages/home/violations_search_page.dart';
import '../pages/profile/profile_page.dart';

class QuickNavigationDrawer extends StatelessWidget {
  const QuickNavigationDrawer({super.key});

  Future<void> _openProfile(BuildContext context) async {
    final token = await SecureStorage.readToken();
    if (!context.mounted) return;

    final l10n = AppLocalizations.of(context);
    if (token == null || token.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(l10n.tr('home.noToken'))),
      );
      return;
    }

    Navigator.of(context).push(
      MaterialPageRoute(builder: (_) => ProfilePage(token: token)),
    );
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);

    return Drawer(
      child: SafeArea(
        child: Column(
          children: [
            Padding(
              padding: const EdgeInsetsDirectional.fromSTEB(16, 12, 16, 8),
              child: Row(
                children: [
                  Expanded(
                    child: Text(
                      l10n.tr('drawer.quickAccess'),
                      style: Theme.of(context).textTheme.titleLarge,
                      textAlign: TextAlign.start,
                    ),
                  ),
                  IconButton(
                    onPressed: () => Navigator.of(context).maybePop(),
                    icon: const Icon(Icons.close),
                  ),
                ],
              ),
            ),
            const Divider(height: 1),
            Expanded(
              child: ListView(
                padding: const EdgeInsetsDirectional.fromSTEB(8, 8, 8, 16),
                children: [
                  _DrawerTile(
                    icon: Icons.home_outlined,
                    label: l10n.tr('drawer.home'),
                    onTap: () {
                      Navigator.of(context).pop();
                      Navigator.of(context).push(
                        MaterialPageRoute(builder: (_) => const HomePage()),
                      );
                    },
                  ),
                  _DrawerTile(
                    icon: Icons.add_a_photo_outlined,
                    label: l10n.tr('drawer.addViolation'),
                    onTap: () {
                      Navigator.of(context).pop();
                      Navigator.of(context).push(
                        MaterialPageRoute(
                          builder: (_) => const AddViolationPage(),
                        ),
                      );
                    },
                  ),
                  _DrawerTile(
                    icon: Icons.list_alt_outlined,
                    label: l10n.tr('drawer.myViolations'),
                    onTap: () {
                      Navigator.of(context).pop();
                      Navigator.of(context).push(
                        MaterialPageRoute(
                          builder: (_) => const MyViolationsPage(),
                        ),
                      );
                    },
                  ),
                  _DrawerTile(
                    icon: Icons.notifications_active_outlined,
                    label: l10n.tr('drawer.dispatchAssignments'),
                    onTap: () {
                      Navigator.of(context).pop();
                      Navigator.of(context).push(
                        MaterialPageRoute(
                          builder: (_) => const DispatchAssignmentsPage(),
                        ),
                      );
                    },
                  ),
                  _DrawerTile(
                    icon: Icons.manage_search_outlined,
                    label: l10n.tr('drawer.searchViolations'),
                    onTap: () {
                      Navigator.of(context).pop();
                      Navigator.of(context).push(
                        MaterialPageRoute(
                          builder: (_) => const ViolationsSearchServerPage(),
                        ),
                      );
                    },
                  ),
                  _DrawerTile(
                    icon: Icons.person_outline,
                    label: l10n.tr('drawer.profile'),
                    onTap: () async {
                      Navigator.of(context).pop();
                      await _openProfile(context);
                    },
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _DrawerTile extends StatelessWidget {
  const _DrawerTile({
    required this.icon,
    required this.label,
    required this.onTap,
  });

  final IconData icon;
  final String label;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return ListTile(
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
      leading: Icon(icon),
      title: Text(
        label,
        textAlign: TextAlign.start,
      ),
      onTap: onTap,
    );
  }
}
