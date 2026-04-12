import 'package:flutter/material.dart';
import 'package:intl/intl.dart';

import '../../models/violation.dart';
import '../../services/api_service.dart';
import '../../services/secure_storage.dart';
import '../../widgets/violation_card.dart';
import 'violation_details_page.dart';

class ViolationsSearchServerPage extends StatefulWidget {
  const ViolationsSearchServerPage({super.key});

  @override
  State<ViolationsSearchServerPage> createState() =>
      _ViolationsSearchServerPageState();
}

class _ViolationsSearchServerPageState
    extends State<ViolationsSearchServerPage> {
  final _plateCtrl = TextEditingController();

  DateTime? fromDate;
  DateTime? toDate;

  bool loading = false;
  bool loadingMore = false;
  String? error;

  List<Violation> items = [];
  int currentPage = 1;
  int lastPage = 1;

  void _resetAndSearch() {
    items.clear();
    currentPage = 1;
    _search(page: 1);
  }

  Future<void> _search({int page = 1}) async {
    setState(() {
      loading = page == 1;
      loadingMore = page > 1;
      error = null;
    });

    try {
      final token = await SecureStorage.readToken();
      if (token == null) throw Exception("No token found, please login");

      final res = await ApiService.searchViolations(
        token,
        plate: _plateCtrl.text,
        from: fromDate == null
            ? null
            : DateFormat('yyyy-MM-dd').format(fromDate!),
        to: toDate == null
            ? null
            : DateFormat('yyyy-MM-dd').format(toDate!),
        perPage: 10,
        page: page,
      );

      final data = res['data'] as List<dynamic>;
      final meta = res['meta'] as Map<String, dynamic>;

      final list = data
          .map((e) => Violation.fromJson(e as Map<String, dynamic>))
          .toList();

      setState(() {
        if (page == 1) {
          items = list;
        } else {
          items.addAll(list);
        }
        currentPage = meta['current_page'] ?? 1;
        lastPage = meta['last_page'] ?? 1;
      });
    } catch (e) {
      setState(() {
        error = e.toString();
      });
    } finally {
      setState(() {
        loading = false;
        loadingMore = false;
      });
    }
  }

  Future<void> _pickFrom() async {
    final picked = await showDatePicker(
      context: context,
      firstDate: DateTime(2020),
      lastDate: DateTime(2100),
      initialDate: fromDate ?? DateTime.now(),
    );
    if (picked == null) return;
    setState(() => fromDate = picked);
  }

  Future<void> _pickTo() async {
    final picked = await showDatePicker(
      context: context,
      firstDate: DateTime(2020),
      lastDate: DateTime(2100),
      initialDate: toDate ?? DateTime.now(),
    );
    if (picked == null) return;
    setState(() => toDate = picked);
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('بحث المخالفات'),
      ),
      body: Container(
        decoration: const BoxDecoration(
          gradient: LinearGradient(
            begin: Alignment.topCenter,
            end: Alignment.bottomCenter,
            colors: [Color(0xFF0A0E21), Color(0xFF0B2A5B)],
          ),
        ),
        child: Column(
          children: [
            // Filters Panel
            Padding(
              padding: const EdgeInsets.all(12),
              child: Container(
                padding: const EdgeInsets.all(12),
                decoration: BoxDecoration(
                  color: Colors.white.withOpacity(0.10),
                  borderRadius: BorderRadius.circular(18),
                  border: Border.all(color: Colors.white.withOpacity(0.12)),
                ),
                child: Column(
                  children: [
                    TextField(
                      controller: _plateCtrl,
                      style: const TextStyle(color: Colors.white),
                      decoration: InputDecoration(
                        labelText: 'رقم السيارة',
                        labelStyle: const TextStyle(color: Colors.white70),
                        prefixIcon:
                            const Icon(Icons.directions_car, color: Colors.white),
                        enabledBorder: OutlineInputBorder(
                          borderRadius: BorderRadius.circular(14),
                          borderSide:
                              BorderSide(color: Colors.white.withOpacity(0.15)),
                        ),
                        focusedBorder: OutlineInputBorder(
                          borderRadius: BorderRadius.circular(14),
                          borderSide:
                              BorderSide(color: Colors.white.withOpacity(0.35)),
                        ),
                      ),
                    ),

                    const SizedBox(height: 10),

                    Row(
                      children: [
                        Expanded(
                          child: _DateChip(
                            label: 'من',
                            value: fromDate,
                            onTap: _pickFrom,
                          ),
                        ),
                        const SizedBox(width: 10),
                        Expanded(
                          child: _DateChip(
                            label: 'إلى',
                            value: toDate,
                            onTap: _pickTo,
                          ),
                        ),
                        const SizedBox(width: 10),
                        ElevatedButton(
                          onPressed: _resetAndSearch,
                          child: const Text('بحث'),
                        ),
                      ],
                    ),
                  ],
                ),
              ),
            ),

            Expanded(
              child: Builder(builder: (_) {
                if (loading && currentPage == 1) {
                  return const Center(child: CircularProgressIndicator());
                }

                if (error != null) {
                  return Center(
                    child: Text(error!, style: const TextStyle(color: Colors.red)),
                  );
                }

                if (items.isEmpty) {
                  return const Center(
                    child: Text(
                      'لا توجد نتائج',
                      style: TextStyle(color: Colors.white),
                    ),
                  );
                }

                return NotificationListener<ScrollNotification>(
                  onNotification: (scrollInfo) {
                    if (!loadingMore &&
                        currentPage < lastPage &&
                        scrollInfo.metrics.pixels >=
                            scrollInfo.metrics.maxScrollExtent - 100) {
                      _search(page: currentPage + 1);
                      return true;
                    }
                    return false;
                  },
                  child: ListView.builder(
                    padding: const EdgeInsets.only(bottom: 12),
                    itemCount: items.length + (loadingMore ? 1 : 0),
                    itemBuilder: (context, index) {
                      if (index >= items.length) {
                        return const Padding(
                          padding: EdgeInsets.all(16),
                          child: Center(child: CircularProgressIndicator()),
                        );
                      }
                      final v = items[index];
                      return Padding(
                        padding: const EdgeInsets.symmetric(
                            horizontal: 12, vertical: 6),
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
                      );
                    },
                  ),
                );
              }),
            ),
          ],
        ),
      ),
    );
  }
}

class _DateChip extends StatelessWidget {
  final String label;
  final DateTime? value;
  final VoidCallback onTap;

  const _DateChip({
    required this.label,
    required this.value,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    final text = value == null
        ? '—'
        : DateFormat('yyyy-MM-dd').format(value!);

    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(14),
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 14),
        decoration: BoxDecoration(
          borderRadius: BorderRadius.circular(14),
          color: Colors.white.withOpacity(0.10),
          border: Border.all(color: Colors.white.withOpacity(0.12)),
        ),
        child: Row(
          children: [
            Text(label, style: const TextStyle(color: Colors.white70)),
            const SizedBox(width: 8),
            Expanded(
              child: Text(text, style: const TextStyle(color: Colors.white)),
            ),
            const Icon(Icons.calendar_today, color: Colors.white, size: 18),
          ],
        ),
      ),
    );
  }
}
