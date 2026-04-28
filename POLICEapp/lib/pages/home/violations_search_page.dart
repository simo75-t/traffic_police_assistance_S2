import 'package:flutter/material.dart';
import 'package:intl/intl.dart';

import '../../l10n/app_localizations.dart';
import '../../models/violation.dart';
import '../../services/api_service.dart';
import '../../services/secure_storage.dart';
import '../../widgets/app_button.dart';
import '../../widgets/app_card.dart';
import '../../widgets/empty_state_widget.dart';
import '../../widgets/loading_widget.dart';
import '../../widgets/quick_navigation_drawer.dart';
import '../../widgets/section_header.dart';
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

      if (!mounted) return;

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
      if (!mounted) return;
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
          throw Exception('auth_required');
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

    throw lastError ?? Exception('search_failed');
  }

  void _resetAndSearch() {
    FocusScope.of(context).unfocus();
    setState(() {
      _items = [];
      _currentPage = 1;
    });
    _search(page: 1);
  }

  void _clearFilters() {
    _plateCtrl.clear();
    setState(() {
      _fromDate = null;
      _toDate = null;
    });
    _resetAndSearch();
  }

  String _localizedError(AppLocalizations l10n) {
    if (_error == null || _error!.trim().isEmpty) {
      return l10n.tr('search.errorFallback');
    }

    if (_error!.contains('auth_required')) {
      return l10n.tr('search.authRequired');
    }

    return _error!;
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

  bool _handleScroll(ScrollNotification scrollInfo) {
    if (!_loading &&
        !_loadingMore &&
        _currentPage < _lastPage &&
        scrollInfo.metrics.pixels >= scrollInfo.metrics.maxScrollExtent - 120) {
      _search(page: _currentPage + 1);
      return true;
    }

    return false;
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    const drawer = QuickNavigationDrawer();

    return Directionality(
      textDirection: l10n.textDirection,
      child: Scaffold(
        drawer: l10n.isRtl ? null : drawer,
        endDrawer: l10n.isRtl ? drawer : null,
        appBar: AppBar(
          title: Text(l10n.tr('search.pageTitle')),
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
        body: RefreshIndicator(
          onRefresh: () async => _resetAndSearch(),
          child: NotificationListener<ScrollNotification>(
            onNotification: _handleScroll,
            child: CustomScrollView(
              physics: const AlwaysScrollableScrollPhysics(),
              slivers: [
                SliverToBoxAdapter(
                  child: Padding(
                    padding:
                        const EdgeInsetsDirectional.fromSTEB(12, 12, 12, 10),
                    child: AppCard(
                      padding:
                          const EdgeInsetsDirectional.fromSTEB(12, 12, 12, 12),
                      child: LayoutBuilder(
                        builder: (context, constraints) {
                          final compact = constraints.maxWidth < 360;

                          return Column(
                            crossAxisAlignment: CrossAxisAlignment.stretch,
                            children: [
                              SectionHeader(
                                title: l10n.tr('search.filtersTitle'),
                                subtitle: l10n.tr('search.filtersSubtitle'),
                              ),
                              const SizedBox(height: 12),
                              TextField(
                                controller: _plateCtrl,
                                textAlign: TextAlign.start,
                                textDirection: l10n.textDirection,
                                decoration: InputDecoration(
                                  labelText: l10n.tr('search.plateNumber'),
                                  prefixIcon: const Icon(
                                    Icons.directions_car_outlined,
                                  ),
                                  suffixIcon: _plateCtrl.text.isEmpty
                                      ? null
                                      : IconButton(
                                          onPressed: () {
                                            _plateCtrl.clear();
                                            setState(() {});
                                          },
                                          icon: const Icon(Icons.clear),
                                        ),
                                ),
                                onChanged: (_) => setState(() {}),
                                onSubmitted: (_) => _resetAndSearch(),
                              ),
                              const SizedBox(height: 10),
                              if (compact)
                                Column(
                                  children: [
                                    _DateField(
                                      label: l10n.tr('search.fromDate'),
                                      value: _fromDate,
                                      onTap: _pickFrom,
                                    ),
                                    const SizedBox(height: 10),
                                    _DateField(
                                      label: l10n.tr('search.toDate'),
                                      value: _toDate,
                                      onTap: _pickTo,
                                    ),
                                  ],
                                )
                              else
                                Row(
                                  children: [
                                    Expanded(
                                      child: _DateField(
                                        label: l10n.tr('search.fromDate'),
                                        value: _fromDate,
                                        onTap: _pickFrom,
                                      ),
                                    ),
                                    const SizedBox(width: 10),
                                    Expanded(
                                      child: _DateField(
                                        label: l10n.tr('search.toDate'),
                                        value: _toDate,
                                        onTap: _pickTo,
                                      ),
                                    ),
                                  ],
                                ),
                              const SizedBox(height: 10),
                              if (compact)
                                Column(
                                  crossAxisAlignment:
                                      CrossAxisAlignment.stretch,
                                  children: [
                                    AppButton(
                                      label: l10n.tr('search.search'),
                                      onPressed: _resetAndSearch,
                                      icon: Icons.search,
                                    ),
                                    const SizedBox(height: 10),
                                    AppButton(
                                      label: l10n.tr('search.reset'),
                                      onPressed: _clearFilters,
                                      icon: Icons.refresh,
                                      variant: AppButtonVariant.secondary,
                                    ),
                                  ],
                                )
                              else
                                Row(
                                  children: [
                                    Expanded(
                                      child: AppButton(
                                        label: l10n.tr('search.search'),
                                        onPressed: _resetAndSearch,
                                        icon: Icons.search,
                                      ),
                                    ),
                                    const SizedBox(width: 10),
                                    Expanded(
                                      child: AppButton(
                                        label: l10n.tr('search.reset'),
                                        onPressed: _clearFilters,
                                        icon: Icons.refresh,
                                        variant: AppButtonVariant.secondary,
                                      ),
                                    ),
                                  ],
                                ),
                              const SizedBox(height: 10),
                              Align(
                                alignment: AlignmentDirectional.centerStart,
                                child: Text(
                                  l10n.tr(
                                    'search.results',
                                    params: {'count': '$_total'},
                                  ),
                                  style: Theme.of(context).textTheme.bodySmall,
                                  textAlign: TextAlign.start,
                                ),
                              ),
                            ],
                          );
                        },
                      ),
                    ),
                  ),
                ),

                if (_loading && _currentPage == 1)
                  SliverFillRemaining(
                    hasScrollBody: false,
                    child: Center(
                      child: LoadingWidget(label: l10n.tr('search.loading')),
                    ),
                  )
                else if (_error != null)
                  SliverFillRemaining(
                    hasScrollBody: false,
                    child: Padding(
                      padding:
                          const EdgeInsetsDirectional.fromSTEB(12, 0, 12, 12),
                      child: EmptyStateWidget(
                        title: l10n.tr('search.errorTitle'),
                        subtitle: _localizedError(l10n),
                        icon: Icons.error_outline,
                        actionLabel: l10n.retry,
                        onAction: _resetAndSearch,
                      ),
                    ),
                  )
                else if (_items.isEmpty)
                  SliverFillRemaining(
                    hasScrollBody: false,
                    child: Padding(
                      padding:
                          const EdgeInsetsDirectional.fromSTEB(12, 0, 12, 12),
                      child: EmptyStateWidget(
                        title: l10n.tr('search.emptyTitle'),
                        subtitle: l10n.tr('search.emptySubtitle'),
                        icon: Icons.search_off_outlined,
                      ),
                    ),
                  )
                else
                  SliverPadding(
                    padding:
                        const EdgeInsetsDirectional.fromSTEB(12, 0, 12, 12),
                    sliver: SliverList(
                      delegate: SliverChildBuilderDelegate(
                        (context, index) {
                          if (index >= _items.length) {
                            return Padding(
                              padding:
                                  const EdgeInsetsDirectional.only(top: 12),
                              child: LoadingWidget(
                                label: l10n.tr('search.loadingMore'),
                                compact: true,
                              ),
                            );
                          }

                          final violation = _items[index];

                          return Padding(
                            padding:
                                const EdgeInsetsDirectional.only(bottom: 10),
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
                        childCount: _items.length + (_loadingMore ? 1 : 0),
                      ),
                    ),
                  ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}

class _DateField extends StatelessWidget {
  const _DateField({
    required this.label,
    required this.value,
    required this.onTap,
  });

  final String label;
  final DateTime? value;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);

    final text = value == null
        ? l10n.tr('search.notSelected')
        : DateFormat('yyyy-MM-dd').format(value!);

    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(16),
      child: InputDecorator(
        decoration: InputDecoration(
          labelText: label,
          prefixIcon: const Icon(Icons.calendar_today_outlined),
          isDense: true,
          contentPadding:
              const EdgeInsetsDirectional.fromSTEB(12, 12, 12, 12),
        ),
        child: Text(
          text,
          textAlign: TextAlign.start,
          textDirection: l10n.textDirection,
        ),
      ),
    );
  }
}
