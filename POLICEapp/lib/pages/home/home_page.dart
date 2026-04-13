import 'package:flutter/material.dart';

import '../../models/profile.dart';
import '../../models/violation.dart';
import '../../services/api_service.dart';
import '../../services/secure_storage.dart';
import '../../widgets/violation_card.dart';
import '../../utils/data_utils.dart';
import '../profile/profile_page.dart';
import 'add_fine_page.dart';
import 'dispatch_assignments_page.dart';
import 'violation_details_page.dart';
import 'my_violation.dart';
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
    await _loadAll();
  }

  /// ✅ FIX: count today using occurred_at, fallback created_at,
  /// and handle "YYYY-MM-DD HH:mm:ss"
  int _countToday(List<Violation> list) {
    final now = DateTime.now();
    int c = 0;

    for (final v in list) {
      final dt = AppDateUtils.violationDate(
        occurredAt: v.occurredAt,
        createdAt: v.createdAt,
      );
      if (dt == null) continue;

      if (AppDateUtils.isSameDay(dt, now)) {
        c++;
      }
    }
    return c;
  }

  /// ✅ FIX: show recent 4 sorted by best date (occurredAt fallback createdAt)
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

      // nulls go last
      if (da == null && db == null) return 0;
      if (da == null) return 1;
      if (db == null) return -1;

      return db.compareTo(da); // desc
    });

    return sorted.take(4).toList();
  }

  @override
  Widget build(BuildContext context) {
    final textTheme = Theme.of(context).textTheme;

    return Scaffold(
      body: Container(
        decoration: const BoxDecoration(
          gradient: LinearGradient(
            begin: Alignment.topCenter,
            end: Alignment.bottomCenter,
            colors: [Color(0xFF0A0E21), Color(0xFF0B2A5B)],
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
                  padding: const EdgeInsets.all(16),
                  children: [
                    // HEADER
                    Row(
                      children: [
                        Expanded(
                          child: FutureBuilder<Profile>(
                            future: _profileFuture,
                            builder: (context, profileSnap) {
                              final name = profileSnap.data?.name ?? '...';
                              return Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Text(
                                    'مرحباً، $name',
                                    style: textTheme.titleMedium?.copyWith(
                                      color: Colors.white,
                                      fontWeight: FontWeight.w800,
                                    ),
                                  ),
                                  const SizedBox(height: 6),
                                  Row(
                                    children: [
                                      Container(
                                        width: 10,
                                        height: 10,
                                        decoration: BoxDecoration(
                                          color: _online
                                              ? Colors.greenAccent
                                              : Colors.redAccent,
                                          shape: BoxShape.circle,
                                        ),
                                      ),
                                      const SizedBox(width: 8),
                                      Text(
                                        _online
                                            ? 'متصل (Online)'
                                            : 'غير متصل (Offline)',
                                        style: textTheme.bodySmall
                                            ?.copyWith(color: Colors.white70),
                                      ),
                                    ],
                                  ),
                                ],
                              );
                            },
                          ),
                        ),
                        IconButton(
                          icon: const Icon(Icons.person,
                              color: Colors.white, size: 28),
                          onPressed: () async {
                            final token =
                                _token ?? await SecureStorage.readToken();
                            if (token == null) {
                              if (!mounted) return;
                              ScaffoldMessenger.of(context).showSnackBar(
                                const SnackBar(
                                  content:
                                      Text("No token found, please login"),
                                ),
                              );
                              return;
                            }

                            Navigator.of(context).push(
                              MaterialPageRoute(
                                builder: (_) => ProfilePage(token: token),
                              ),
                            );
                          },
                        ),
                      ],
                    ),

                    const SizedBox(height: 18),

                    // QUICK STATS
                    Row(
                      children: [
                        Expanded(
                          child: _StatCard(
                            title: 'مخالفات اليوم',
                            value: loading ? '...' : '$todayCount',
                            icon: Icons.receipt_long,
                          ),
                        ),
                        const SizedBox(width: 12),
                        Expanded(
                          child: _StatCard(
                            title: 'إجمالي المخالفات',
                            value: loading ? '...' : '$total',
                            icon: Icons.local_police,
                          ),
                        ),
                      ],
                    ),

                    const SizedBox(height: 18),

                    // ACTION BUTTONS
                    _PrimaryActionButton(
                      title: 'تسجيل مخالفة جديدة',
                      subtitle: 'OCR + تصوير اللوحة',
                      icon: Icons.add_a_photo,
                      onTap: () async {
                        final created = await Navigator.push(
                          context,
                          MaterialPageRoute(
                            builder: (_) => const AddViolationPage(),
                          ),
                        );

                        if (created == true) {
                          await _refresh(); // ✅ force reload
                        }
                      },
                    ),

                    const SizedBox(height: 12),

                    _SecondaryActionButton(
                      title: 'Violations',
                      subtitle: 'عرض كل المخالفات التي سجلتها',
                      icon: Icons.list_alt,
                      onTap: () {
                        Navigator.push(
                          context,
                          MaterialPageRoute(
                            builder: (_) => const MyViolationsPage(),
                          ),
                        );
                      },
                    ),

                    const SizedBox(height: 12),

                    _SecondaryActionButton(
                      title: 'Dispatch',
                      subtitle: 'Active citizen reports assigned to you',
                      icon: Icons.notifications_active,
                      onTap: () {
                        Navigator.push(
                          context,
                          MaterialPageRoute(
                            builder: (_) => const DispatchAssignmentsPage(),
                          ),
                        );
                      },
                    ),

                    const SizedBox(height: 12),

                    _SecondaryActionButton(
                      title: 'Search',
                      subtitle: 'Search violations by plate and date',
                      icon: Icons.manage_search,
                      onTap: () {
                        Navigator.push(
                          context,
                          MaterialPageRoute(
                            builder: (_) => const ViolationsSearchServerPage(),
                          ),
                        );
                      },
                    ),

                    const SizedBox(height: 22),

                    Text(
                      'المخالفات الأخيرة',
                      style: textTheme.titleMedium?.copyWith(
                        color: Colors.white,
                        fontWeight: FontWeight.w800,
                      ),
                    ),
                    const SizedBox(height: 10),

                    if (loading)
                      const Center(
                        child: Padding(
                          padding: EdgeInsets.all(16),
                          child: CircularProgressIndicator(),
                        ),
                      )
                    else if (hasError)
                      Padding(
                        padding: const EdgeInsets.all(12),
                        child: Text(
                          'فشل تحميل البيانات: ${violationsSnap.error}',
                          style: const TextStyle(color: Colors.white70),
                        ),
                      )
                    else if (recent.isEmpty)
                      const Padding(
                        padding: EdgeInsets.all(12),
                        child: Text(
                          "No violations found",
                          style: TextStyle(color: Colors.white, fontSize: 16),
                        ),
                      )
                    else
                      ...recent.map(
                        (v) => Padding(
                          padding: const EdgeInsets.only(bottom: 10),
                          child: ViolationCard(
                            violation: v,
                            onTap: () {
                              Navigator.push(
                                context,
                                MaterialPageRoute(
                                  builder: (_) =>
                                      ViolationDetailsPage(violation: v),
                                ),
                              );
                            },
                          ),
                        ),
                      ),

                    const SizedBox(height: 8),
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

class _StatCard extends StatelessWidget {
  final String title;
  final String value;
  final IconData icon;

  const _StatCard({
    required this.title,
    required this.value,
    required this.icon,
  });

  @override
  Widget build(BuildContext context) {
    final textTheme = Theme.of(context).textTheme;

    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: Colors.white.withOpacity(0.10),
        borderRadius: BorderRadius.circular(18),
        border: Border.all(color: Colors.white.withOpacity(0.12)),
      ),
      child: Row(
        children: [
          Container(
            width: 38,
            height: 38,
            decoration: BoxDecoration(
              color: Colors.white.withOpacity(0.12),
              borderRadius: BorderRadius.circular(12),
            ),
            child: Icon(icon, color: Colors.white),
          ),
          const SizedBox(width: 10),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(title,
                    style:
                        textTheme.bodySmall?.copyWith(color: Colors.white70)),
                const SizedBox(height: 4),
                Text(
                  value,
                  style: textTheme.titleLarge?.copyWith(
                    color: Colors.white,
                    fontWeight: FontWeight.w900,
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _PrimaryActionButton extends StatelessWidget {
  final String title;
  final String subtitle;
  final IconData icon;
  final VoidCallback onTap;

  const _PrimaryActionButton({
    required this.title,
    required this.subtitle,
    required this.icon,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    final textTheme = Theme.of(context).textTheme;

    return InkWell(
      borderRadius: BorderRadius.circular(20),
      onTap: onTap,
      child: Container(
        padding: const EdgeInsets.all(16),
        decoration: BoxDecoration(
          color: const Color(0xFF1E88FF),
          borderRadius: BorderRadius.circular(20),
        ),
        child: Row(
          children: [
            Container(
              width: 44,
              height: 44,
              decoration: BoxDecoration(
                color: Colors.white.withOpacity(0.18),
                borderRadius: BorderRadius.circular(14),
              ),
              child: Icon(icon, color: Colors.white),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    title,
                    style: textTheme.titleMedium?.copyWith(
                      color: Colors.white,
                      fontWeight: FontWeight.w900,
                    ),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    subtitle,
                    style: textTheme.bodySmall
                        ?.copyWith(color: Colors.white.withOpacity(0.9)),
                  ),
                ],
              ),
            ),
            const Icon(Icons.arrow_forward_ios, color: Colors.white, size: 16),
          ],
        ),
      ),
    );
  }
}

class _SecondaryActionButton extends StatelessWidget {
  final String title;
  final String subtitle;
  final IconData icon;
  final VoidCallback onTap;

  const _SecondaryActionButton({
    required this.title,
    required this.subtitle,
    required this.icon,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    final textTheme = Theme.of(context).textTheme;

    return InkWell(
      borderRadius: BorderRadius.circular(20),
      onTap: onTap,
      child: Container(
        padding: const EdgeInsets.all(16),
        decoration: BoxDecoration(
          color: Colors.white.withOpacity(0.10),
          borderRadius: BorderRadius.circular(20),
          border: Border.all(color: Colors.white.withOpacity(0.12)),
        ),
        child: Row(
          children: [
            Container(
              width: 44,
              height: 44,
              decoration: BoxDecoration(
                color: Colors.white.withOpacity(0.12),
                borderRadius: BorderRadius.circular(14),
              ),
              child: Icon(icon, color: Colors.white),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    title,
                    style: textTheme.titleMedium?.copyWith(
                      color: Colors.white,
                      fontWeight: FontWeight.w900,
                    ),
                  ),
                  const SizedBox(height: 4),
                  Text(subtitle,
                      style:
                          textTheme.bodySmall?.copyWith(color: Colors.white70)),
                ],
              ),
            ),
            const Icon(Icons.arrow_forward_ios, color: Colors.white, size: 16),
          ],
        ),
      ),
    );
  }
}
