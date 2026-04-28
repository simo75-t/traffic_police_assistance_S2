import 'dart:async';

import 'package:flutter/material.dart';

import '../../core/police_theme.dart';
import '../../l10n/app_localizations.dart';
import '../../models/profile.dart';
import '../../models/violation.dart';
import '../../services/api_service.dart';
import '../../services/officer_presence_service.dart';
import '../../services/secure_storage.dart';
import '../../utils/data_utils.dart';
import '../../widgets/app_card.dart';
import '../../widgets/empty_state_widget.dart';
import '../../widgets/loading_widget.dart';
import '../../widgets/quick_navigation_drawer.dart';
import '../../widgets/section_header.dart';
import '../../widgets/status_badge.dart';
import '../../widgets/violation_card.dart';
import '../profile/profile_page.dart';
import 'add_fine_page.dart';
import 'dispatch_assignments_page.dart';
import 'my_violation.dart';
import 'violation_details_page.dart';
import 'violations_search_page.dart';

class HomePage extends StatefulWidget {
  const HomePage({super.key});

  @override
  State<HomePage> createState() => _HomePageState();
}

class _HomePageState extends State<HomePage> {
  Future<List<Violation>> _violationsFuture = Future.value(<Violation>[]);
  Future<Profile> _profileFuture = Future.error('Not loaded');

  bool _online = false;
  String? _token;

  @override
  void initState() {
    super.initState();
    _loadAll();
    unawaited(OfficerPresenceService.start());
  }

  Future<void> _loadAll() async {
    final token = await SecureStorage.readToken();
    _token = token;

    if (!mounted) return;

    if (token == null) {
      setState(() {
        _online = false;
        _violationsFuture = Future.value(<Violation>[]);
        _profileFuture = Future.error('No token found');
      });
      return;
    }

    setState(() {
      _online = false;

      _profileFuture = ApiService.getProfile(token).catchError((e) {
        if (mounted) setState(() => _online = false);
        throw e;
      }).then((p) {
        if (mounted) setState(() => _online = true);
        return p;
      });

      _violationsFuture = ApiService.getViolations(token).catchError((e) {
        throw e;
      });
    });
  }

  Future<void> _refresh() async {
    await OfficerPresenceService.syncNow();
    await _loadAll();
  }

  int _countToday(List<Violation> list) {
    final now = DateTime.now();
    var count = 0;

    for (final violation in list) {
      final dt = AppDateUtils.violationDate(
        occurredAt: violation.occurredAt,
        createdAt: violation.createdAt,
      );
      if (dt == null) continue;

      if (AppDateUtils.isSameDay(dt, now)) {
        count++;
      }
    }
    return count;
  }

  List<Violation> _recentSorted(List<Violation> list) {
    final sorted = List<Violation>.from(list);

    sorted.sort((a, b) {
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

    return sorted.take(4).toList();
  }

  Future<void> _openProfile() async {
    final token = _token ?? await SecureStorage.readToken();
    if (!mounted) return;

    if (token == null) {
      final l10n = AppLocalizations.of(context);
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(l10n.tr('home.noToken'))),
      );
      return;
    }

    Navigator.of(context).push(
      MaterialPageRoute(
        builder: (_) => ProfilePage(token: token),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    final textTheme = Theme.of(context).textTheme;
    const drawer = QuickNavigationDrawer();

    return Scaffold(
      drawer: l10n.isRtl ? null : drawer,
      endDrawer: l10n.isRtl ? drawer : null,
      appBar: AppBar(
        title: Text(l10n.appTitle),
        leading: l10n.isRtl
            ? null
            : Builder(
                builder: (context) => IconButton(
                  onPressed: () => Scaffold.of(context).openDrawer(),
                  icon: const Icon(Icons.menu),
                ),
              ),
        actions: [
          if (l10n.isRtl)
            Builder(
              builder: (context) => IconButton(
                onPressed: () => Scaffold.of(context).openEndDrawer(),
                icon: const Icon(Icons.menu),
              ),
            ),
        ],
      ),
      body: Container(
        decoration: const BoxDecoration(
          gradient: LinearGradient(
            begin: Alignment.topCenter,
            end: Alignment.bottomCenter,
            colors: [Color(0xFFF5F8FC), Color(0xFFE8EEF6)],
          ),
        ),
        child: SafeArea(
          child: FutureBuilder<List<Violation>>(
            future: _violationsFuture,
            builder: (context, violationsSnap) {
              final loading =
                  violationsSnap.connectionState == ConnectionState.waiting;
              final hasError = violationsSnap.hasError;
              final list = violationsSnap.data ?? <Violation>[];
              final recent = _recentSorted(list);
              final total = list.length;
              final todayCount = _countToday(list);

              return RefreshIndicator(
                onRefresh: _refresh,
                child: ListView(
                  padding: const EdgeInsetsDirectional.fromSTEB(16, 16, 16, 16),
                  children: [
                    FutureBuilder<Profile>(
                      future: _profileFuture,
                      builder: (context, profileSnap) {
                        final name =
                            profileSnap.data?.name ?? l10n.tr('home.officerFallback');

                        return AppCard(
                          padding: EdgeInsets.zero,
                          backgroundColor: PoliceTheme.primary,
                          borderColor: PoliceTheme.primary,
                          child: Container(
                            padding: const EdgeInsets.all(20),
                            decoration: BoxDecoration(
                              borderRadius: BorderRadius.circular(24),
                              gradient: const LinearGradient(
                                colors: [PoliceTheme.primary, PoliceTheme.secondary],
                                begin: AlignmentDirectional.topStart,
                                end: AlignmentDirectional.bottomEnd,
                              ),
                            ),
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Row(
                                  children: [
                                    Expanded(
                                      child: Column(
                                        crossAxisAlignment:
                                            CrossAxisAlignment.start,
                                        children: [
                                          Text(
                                            l10n.tr(
                                              'home.welcomeBack',
                                              params: {'name': name},
                                            ),
                                            style: textTheme.titleLarge?.copyWith(
                                              color: Colors.white,
                                            ),
                                            textAlign: TextAlign.start,
                                          ),
                                          const SizedBox(height: 8),
                                          Text(
                                            l10n.tr('home.welcomeSubtitle'),
                                            style: textTheme.bodyMedium?.copyWith(
                                              color: Colors.white.withValues(
                                                alpha: 0.82,
                                              ),
                                            ),
                                            textAlign: TextAlign.start,
                                          ),
                                        ],
                                      ),
                                    ),
                                    IconButton(
                                      onPressed: _openProfile,
                                      style: IconButton.styleFrom(
                                        backgroundColor:
                                            Colors.white.withValues(alpha: 0.12),
                                      ),
                                      icon: const Icon(
                                        Icons.person_outline,
                                        color: Colors.white,
                                      ),
                                    ),
                                  ],
                                ),
                                const SizedBox(height: 20),
                                Wrap(
                                  spacing: 8,
                                  runSpacing: 8,
                                  children: [
                                    StatusBadge(
                                      label: _online
                                          ? l10n.tr('home.online')
                                          : l10n.tr('home.offline'),
                                      color: _online
                                          ? PoliceTheme.success
                                          : PoliceTheme.error,
                                    ),
                                    StatusBadge(
                                      label: l10n.tr(
                                        'home.todayCount',
                                        params: {'count': '$todayCount'},
                                      ),
                                      color: PoliceTheme.accent,
                                    ),
                                  ],
                                ),
                                const SizedBox(height: 20),
                                Row(
                                  children: [
                                    Expanded(
                                      child: _MetricTile(
                                        label: l10n.tr('home.metricToday'),
                                        value: '$todayCount',
                                        icon: Icons.today_outlined,
                                      ),
                                    ),
                                    const SizedBox(width: 12),
                                    Expanded(
                                      child: _MetricTile(
                                        label:
                                            l10n.tr('home.metricTotalViolations'),
                                        value: '$total',
                                        icon: Icons.shield_outlined,
                                      ),
                                    ),
                                  ],
                                ),
                              ],
                            ),
                          ),
                        );
                      },
                    ),
                    const SizedBox(height: 16),
                    SectionHeader(
                      title: l10n.tr('home.quickActionsTitle'),
                      subtitle: l10n.tr('home.quickActionsSubtitle'),
                    ),
                    const SizedBox(height: 10),
                    _ActionTile(
                      title: l10n.tr('home.actionRegisterTitle'),
                      subtitle: l10n.tr('home.actionRegisterSubtitle'),
                      icon: Icons.add_a_photo_outlined,
                      accent: PoliceTheme.primary,
                      onTap: () async {
                        final created = await Navigator.push(
                          context,
                          MaterialPageRoute(
                            builder: (_) => const AddViolationPage(),
                          ),
                        );

                        if (created == true) {
                          await _refresh();
                        }
                      },
                    ),
                    const SizedBox(height: 10),
                    _ActionTile(
                      title: l10n.tr('home.actionMyViolationsTitle'),
                      subtitle: l10n.tr('home.actionMyViolationsSubtitle'),
                      icon: Icons.list_alt_outlined,
                      accent: PoliceTheme.secondary,
                      onTap: () {
                        Navigator.push(
                          context,
                          MaterialPageRoute(
                            builder: (_) => const MyViolationsPage(),
                          ),
                        );
                      },
                    ),
                    const SizedBox(height: 10),
                    _ActionTile(
                      title: l10n.tr('home.actionDispatchTitle'),
                      subtitle: l10n.tr('home.actionDispatchSubtitle'),
                      icon: Icons.notifications_active_outlined,
                      accent: PoliceTheme.processing,
                      onTap: () {
                        Navigator.push(
                          context,
                          MaterialPageRoute(
                            builder: (_) => const DispatchAssignmentsPage(),
                          ),
                        );
                      },
                    ),
                    const SizedBox(height: 10),
                    _ActionTile(
                      title: l10n.tr('home.actionSearchTitle'),
                      subtitle: l10n.tr('home.actionSearchSubtitle'),
                      icon: Icons.manage_search_outlined,
                      accent: PoliceTheme.accent,
                      onTap: () {
                        Navigator.push(
                          context,
                          MaterialPageRoute(
                            builder: (_) =>
                                const ViolationsSearchServerPage(),
                          ),
                        );
                      },
                    ),
                    const SizedBox(height: 20),
                    SectionHeader(
                      title: l10n.tr('home.recentTitle'),
                      subtitle: l10n.tr('home.recentSubtitle'),
                    ),
                    const SizedBox(height: 10),
                    if (loading)
                      LoadingWidget(label: l10n.tr('home.loadingRecent'))
                    else if (hasError)
                      EmptyStateWidget(
                        title: l10n.tr('home.errorTitle'),
                        subtitle: '${violationsSnap.error}',
                        icon: Icons.error_outline,
                        actionLabel: l10n.retry,
                        onAction: _refresh,
                      )
                    else if (recent.isEmpty)
                      EmptyStateWidget(
                        title: l10n.tr('home.emptyTitle'),
                        subtitle: l10n.tr('home.emptySubtitle'),
                        icon: Icons.fact_check_outlined,
                      )
                    else
                      ...recent.map(
                        (violation) => Padding(
                          padding: const EdgeInsets.only(bottom: 12),
                          child: ViolationCard(
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
                        ),
                      ),
                  ],
                ),
              );
            },
          ),
        ),
      ),
    );
  }
}

class _MetricTile extends StatelessWidget {
  const _MetricTile({
    required this.label,
    required this.value,
    required this.icon,
  });

  final String label;
  final String value;
  final IconData icon;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: Colors.white.withValues(alpha: 0.12),
        borderRadius: BorderRadius.circular(18),
      ),
      child: Row(
        children: [
          Container(
            width: 38,
            height: 38,
            decoration: BoxDecoration(
              color: Colors.white.withValues(alpha: 0.14),
              borderRadius: BorderRadius.circular(12),
            ),
            child: Icon(icon, color: Colors.white),
          ),
          const SizedBox(width: 10),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  label,
                  style: Theme.of(context).textTheme.bodySmall?.copyWith(
                        color: Colors.white70,
                      ),
                  textAlign: TextAlign.start,
                ),
                const SizedBox(height: 2),
                Text(
                  value,
                  style: Theme.of(context).textTheme.titleSmall?.copyWith(
                        color: Colors.white,
                      ),
                  textAlign: TextAlign.start,
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _ActionTile extends StatelessWidget {
  const _ActionTile({
    required this.title,
    required this.subtitle,
    required this.icon,
    required this.accent,
    required this.onTap,
  });

  final String title;
  final String subtitle;
  final IconData icon;
  final Color accent;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);

    return AppCard(
      onTap: onTap,
      padding: const EdgeInsetsDirectional.fromSTEB(14, 14, 14, 14),
      child: Row(
        children: [
          Container(
            width: 46,
            height: 46,
            decoration: BoxDecoration(
              color: accent.withValues(alpha: 0.12),
              borderRadius: BorderRadius.circular(16),
            ),
            child: Icon(icon, color: accent),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  title,
                  style: Theme.of(context).textTheme.titleSmall,
                  textAlign: TextAlign.start,
                ),
                const SizedBox(height: 2),
                Text(
                  subtitle,
                  style: Theme.of(context).textTheme.bodyMedium,
                  textAlign: TextAlign.start,
                  maxLines: 2,
                  overflow: TextOverflow.ellipsis,
                ),
              ],
            ),
          ),
          const SizedBox(width: 8),
          Icon(
            l10n.isRtl
                ? Icons.chevron_left_rounded
                : Icons.chevron_right_rounded,
            color: PoliceTheme.textSecondary,
          ),
        ],
      ),
    );
  }
}
