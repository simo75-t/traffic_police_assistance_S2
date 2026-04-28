import 'package:flutter/material.dart';

import '../../l10n/app_localizations.dart';
import '../../models/violation.dart';
import '../../services/api_service.dart';
import '../../services/secure_storage.dart';
import '../../utils/data_utils.dart';
import '../../widgets/app_button.dart';
import '../../widgets/empty_state_widget.dart';
import '../../widgets/loading_widget.dart';
import '../../widgets/section_header.dart';
import '../../widgets/violation_card.dart';
import 'violation_details_page.dart';

class MyViolationsPage extends StatefulWidget {
  const MyViolationsPage({super.key});

  @override
  State<MyViolationsPage> createState() => _MyViolationsPageState();
}

class _MyViolationsPageState extends State<MyViolationsPage> {
  bool _loading = true;
  String? _error;
  List<Violation> _items = [];

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });

    try {
      final token = await SecureStorage.readToken();
      if (token == null || token.isEmpty) {
        setState(() {
          _error = 'auth_required';
          _loading = false;
        });
        return;
      }

      final list = await ApiService.getViolations(token);

      list.sort((a, b) {
        final da = AppDateUtils.violationDate(
          occurredAt: a.occurredAt,
          createdAt: a.createdAt,
        );
        final db = AppDateUtils.violationDate(
          occurredAt: b.occurredAt,
          createdAt: b.createdAt,
        );

        if (da == null && db == null) return 0;
        if (da == null) return 1;
        if (db == null) return -1;

        return db.compareTo(da);
      });

      setState(() {
        _items = list;
        _loading = false;
      });
    } catch (e) {
      setState(() {
        _error = e.toString();
        _loading = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    final localizedError =
        _error == 'auth_required' ? l10n.violationsLoginRequired : _error;

    return Scaffold(
      appBar: AppBar(
        title: Text(l10n.violationsPageTitle),
        actions: [
          IconButton(
            onPressed: _load,
            icon: const Icon(Icons.refresh),
          ),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: _load,
        child: ListView(
          padding: const EdgeInsets.all(16),
          children: [
            SectionHeader(
              title: l10n.violationsSectionTitle,
              subtitle: l10n.violationsSectionSubtitle,
            ),
            const SizedBox(height: 16),
            if (_loading)
              LoadingWidget(label: l10n.violationsLoading)
            else if (_error != null)
              Column(
                children: [
                  EmptyStateWidget(
                    title: l10n.violationsErrorTitle,
                    subtitle: localizedError ?? '',
                    icon: Icons.error_outline,
                  ),
                  const SizedBox(height: 12),
                  AppButton(
                    label: l10n.tryAgain,
                    onPressed: _load,
                    icon: Icons.refresh,
                    variant: AppButtonVariant.secondary,
                  ),
                ],
              )
            else if (_items.isEmpty)
              EmptyStateWidget(
                title: l10n.violationsEmptyTitle,
                subtitle: l10n.violationsEmptySubtitle,
                icon: Icons.fact_check_outlined,
              )
            else ...[
              for (final violation in _items) ...[
                ViolationCard(
                  violation: violation,
                  onTap: () {
                    Navigator.push(
                      context,
                      MaterialPageRoute(
                        builder: (_) =>
                            ViolationDetailsPage(violation: violation),
                      ),
                    );
                  },
                ),
                const SizedBox(height: 12),
              ],
            ],
          ],
        ),
      ),
    );
  }
}
