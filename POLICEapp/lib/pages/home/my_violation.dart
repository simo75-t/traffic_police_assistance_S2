import 'package:flutter/material.dart';
import '../../services/api_service.dart';
import '../../services/secure_storage.dart';
import '../../models/violation.dart';
import 'violation_details_page.dart';
import '../../widgets/violation_card.dart';
import '../../utils/data_utils.dart';

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
          _error = 'Please login first.';
          _loading = false;
        });
        return;
      }

      final list = await ApiService.getViolations(token);

      // ✅ SORT by best date (occurred_at fallback created_at)
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

        return db.compareTo(da); // desc
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
    return Scaffold(
      appBar: AppBar(
        title: const Text('مخالفاتي'),
        actions: [
          IconButton(
            onPressed: _load,
            icon: const Icon(Icons.refresh),
          ),
        ],
      ),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : _error != null
              ? Center(
                  child: Padding(
                    padding: const EdgeInsets.all(16),
                    child: Column(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Text(
                          _error!,
                          textAlign: TextAlign.center,
                          style: const TextStyle(color: Colors.redAccent),
                        ),
                        const SizedBox(height: 12),
                        ElevatedButton.icon(
                          onPressed: _load,
                          icon: const Icon(Icons.refresh),
                          label: const Text('Try again'),
                        ),
                      ],
                    ),
                  ),
                )
              : RefreshIndicator(
                  onRefresh: _load,
                  child: _items.isEmpty
                      ? ListView(
                          children: const [
                            SizedBox(height: 140),
                            Center(child: Text('ما عندك مخالفات حالياً')),
                          ],
                        )
                      : ListView.separated(
                          padding: const EdgeInsets.all(16),
                          itemCount: _items.length,
                          separatorBuilder: (_, __) =>
                              const SizedBox(height: 10),
                          itemBuilder: (context, i) {
                            final v = _items[i];

                            return ViolationCard(
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
                            );
                          },
                        ),
                ),
    );
  }
}
