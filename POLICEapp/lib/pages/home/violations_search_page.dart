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

  DateTime? _fromDate;
  DateTime? _toDate;
  bool _loading = false;
  bool _loadingMore = false;
  String? _error;
  List<Violation> _items = [];
  int _currentPage = 1;
  int _lastPage = 1;
  int _total = 0;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (mounted) {
        _search(page: 1);
      }
    });
  }

  @override
  void dispose() {
    _plateCtrl.dispose();
    super.dispose();
  }

  Future<void> _search({int page = 1}) async {
    setState(() {
      _loading = page == 1;
      _loadingMore = page > 1;
      _error = null;
    });

    try {
      final result = await _runSearchRequest(page: page);
      final data = result.$1;
      final meta = result.$2;

      setState(() {
        if (page == 1) {
          _items = data;
        } else {
          _items.addAll(data);
        }
        _currentPage = (meta['current_page'] as num?)?.toInt() ?? page;
        _lastPage = (meta['last_page'] as num?)?.toInt() ?? 1;
        _total = (meta['total'] as num?)?.toInt() ?? _items.length;
      });
    } catch (e) {
      setState(() => _error = e.toString());
    } finally {
      if (mounted) {
        setState(() {
          _loading = false;
          _loadingMore = false;
        });
      }
    }
  }

  Future<(List<Violation>, Map<String, dynamic>)> _runSearchRequest({
    required int page,
  }) async {
    Object? lastError;

    for (var attempt = 0; attempt < 2; attempt++) {
      try {
        final token = await SecureStorage.readToken();
        if (token == null || token.isEmpty) {
          throw Exception('يجب تسجيل الدخول أولاً');
        }

        final res = await ApiService.searchViolations(
          token,
          plate: _plateCtrl.text,
          from: _fromDate == null
              ? null
              : DateFormat('yyyy-MM-dd').format(_fromDate!),
          to: _toDate == null
              ? null
              : DateFormat('yyyy-MM-dd').format(_toDate!),
          perPage: 10,
          page: page,
        );

        final rawData = res['data'];
        final rawMeta = res['meta'];

        final data = (rawData is List ? rawData : const <dynamic>[])
            .whereType<Map>()
            .map((e) => Violation.fromJson(Map<String, dynamic>.from(e)))
            .toList();
        final meta = rawMeta is Map
            ? Map<String, dynamic>.from(rawMeta)
            : <String, dynamic>{};

        return (data, meta);
      } catch (e) {
        lastError = e;
        if (attempt == 0) {
          await Future.delayed(const Duration(milliseconds: 350));
          continue;
        }
      }
    }

    throw lastError ?? Exception('تعذر تنفيذ البحث');
  }

  void _resetAndSearch() {
    FocusScope.of(context).unfocus();
    setState(() {
      _items = [];
      _currentPage = 1;
    });
    _search(page: 1);
  }

  Future<void> _pickFrom() async {
    final picked = await showDatePicker(
      context: context,
      firstDate: DateTime(2020),
      lastDate: DateTime(2100),
      initialDate: _fromDate ?? DateTime.now(),
    );
    if (picked == null) return;
    setState(() => _fromDate = picked);
  }

  Future<void> _pickTo() async {
    final picked = await showDatePicker(
      context: context,
      firstDate: DateTime(2020),
      lastDate: DateTime(2100),
      initialDate: _toDate ?? DateTime.now(),
    );
    if (picked == null) return;
    setState(() => _toDate = picked);
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('البحث في المخالفات')),
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
            Padding(
              padding: const EdgeInsets.all(12),
              child: Container(
                padding: const EdgeInsets.all(14),
                decoration: BoxDecoration(
                  color: Colors.white.withValues(alpha: 0.10),
                  borderRadius: BorderRadius.circular(20),
                  border: Border.all(
                    color: Colors.white.withValues(alpha: 0.12),
                  ),
                ),
                child: Column(
                  children: [
                    TextField(
                      controller: _plateCtrl,
                      style: const TextStyle(color: Colors.white),
                      decoration: InputDecoration(
                        labelText: 'رقم المركبة',
                        labelStyle: const TextStyle(color: Colors.white70),
                        prefixIcon: const Icon(
                          Icons.directions_car_outlined,
                          color: Colors.white,
                        ),
                        suffixIcon: _plateCtrl.text.isEmpty
                            ? null
                            : IconButton(
                                onPressed: () {
                                  _plateCtrl.clear();
                                  setState(() {});
                                },
                                icon: const Icon(
                                  Icons.clear,
                                  color: Colors.white70,
                                ),
                              ),
                      ),
                      onChanged: (_) => setState(() {}),
                      onSubmitted: (_) => _resetAndSearch(),
                    ),
                    const SizedBox(height: 12),
                    Row(
                      children: [
                        Expanded(
                          child: _DateChip(
                            label: 'من تاريخ',
                            value: _fromDate,
                            onTap: _pickFrom,
                          ),
                        ),
                        const SizedBox(width: 10),
                        Expanded(
                          child: _DateChip(
                            label: 'إلى تاريخ',
                            value: _toDate,
                            onTap: _pickTo,
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 12),
                    Row(
                      children: [
                        Expanded(
                          child: ElevatedButton.icon(
                            onPressed: _resetAndSearch,
                            icon: const Icon(Icons.search),
                            label: const Text('بحث'),
                          ),
                        ),
                        const SizedBox(width: 10),
                        Expanded(
                          child: OutlinedButton.icon(
                            onPressed: () {
                              _plateCtrl.clear();
                              setState(() {
                                _fromDate = null;
                                _toDate = null;
                              });
                              _resetAndSearch();
                            },
                            icon: const Icon(Icons.refresh),
                            label: const Text('إعادة تعيين'),
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 10),
                    Align(
                      alignment: Alignment.centerRight,
                      child: Text(
                        'عدد النتائج: $_total',
                        style: const TextStyle(color: Colors.white70),
                      ),
                    ),
                  ],
                ),
              ),
            ),
            Expanded(
              child: Builder(
                builder: (_) {
                  if (_loading && _currentPage == 1) {
                    return const Center(child: CircularProgressIndicator());
                  }

                  if (_error != null) {
                    return Center(
                      child: Padding(
                        padding: const EdgeInsets.all(24),
                        child: Text(
                          'تعذر تنفيذ البحث:\n$_error',
                          textAlign: TextAlign.center,
                          style: const TextStyle(color: Colors.white),
                        ),
                      ),
                    );
                  }

                  if (_items.isEmpty) {
                    return const Center(
                      child: Text(
                        'لا توجد مخالفات مطابقة للبحث.',
                        style: TextStyle(color: Colors.white),
                      ),
                    );
                  }

                  return NotificationListener<ScrollNotification>(
                    onNotification: (scrollInfo) {
                      if (!_loadingMore &&
                          _currentPage < _lastPage &&
                          scrollInfo.metrics.pixels >=
                              scrollInfo.metrics.maxScrollExtent - 100) {
                        _search(page: _currentPage + 1);
                        return true;
                      }
                      return false;
                    },
                    child: ListView.builder(
                      padding: const EdgeInsets.only(bottom: 12),
                      itemCount: _items.length + (_loadingMore ? 1 : 0),
                      itemBuilder: (context, index) {
                        if (index >= _items.length) {
                          return const Padding(
                            padding: EdgeInsets.all(16),
                            child: Center(child: CircularProgressIndicator()),
                          );
                        }

                        final violation = _items[index];
                        return Padding(
                          padding: const EdgeInsets.symmetric(
                            horizontal: 12,
                            vertical: 6,
                          ),
                          child: ViolationCard(
                            violation: violation,
                            onTap: () {
                              Navigator.push(
                                context,
                                MaterialPageRoute(
                                  builder: (_) => ViolationDetailsPage(
                                    violation: violation,
                                  ),
                                ),
                              );
                            },
                          ),
                        );
                      },
                    ),
                  );
                },
              ),
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
    final text =
        value == null ? 'غير محدد' : DateFormat('yyyy-MM-dd').format(value!);

    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(14),
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 14),
        decoration: BoxDecoration(
          borderRadius: BorderRadius.circular(14),
          color: Colors.white.withValues(alpha: 0.10),
          border: Border.all(color: Colors.white.withValues(alpha: 0.12)),
        ),
        child: Row(
          children: [
            const Icon(Icons.calendar_today, color: Colors.white, size: 18),
            const SizedBox(width: 8),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(label, style: const TextStyle(color: Colors.white70)),
                  const SizedBox(height: 4),
                  Text(text, style: const TextStyle(color: Colors.white)),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}
