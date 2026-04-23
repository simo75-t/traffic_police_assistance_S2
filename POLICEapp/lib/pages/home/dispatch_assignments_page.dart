import 'package:flutter/material.dart';

import '../../models/dispatch_assignment.dart';
import '../../services/api_service.dart';
import '../../services/secure_storage.dart';

class DispatchAssignmentsPage extends StatefulWidget {
  const DispatchAssignmentsPage({super.key, this.highlightId});

  final int? highlightId;

  @override
  State<DispatchAssignmentsPage> createState() =>
      _DispatchAssignmentsPageState();
}

class _DispatchAssignmentsPageState extends State<DispatchAssignmentsPage> {
  Future<List<DispatchAssignment>> _future = Future.value(const []);

  @override
  void initState() {
    super.initState();
    _future = _loadAssignments();
  }

  Future<List<DispatchAssignment>> _loadAssignments() async {
    final token = await SecureStorage.readToken();
    if (token == null || token.isEmpty) {
      throw Exception('يجب تسجيل الدخول أولاً');
    }

    return ApiService.getDispatchAssignments(token);
  }

  Future<void> _refresh() async {
    final future = _loadAssignments();
    setState(() {
      _future = future;
    });
    await future;
  }

  Future<void> _completeAssignment(DispatchAssignment assignment) async {
    final token = await SecureStorage.readToken();
    if (token == null || token.isEmpty) return;

    final notesController = TextEditingController();
    if (!mounted) return;

    final confirmed = await showModalBottomSheet<bool>(
      context: context,
      isScrollControlled: true,
      builder: (context) {
        return Padding(
          padding: EdgeInsets.only(
            right: 20,
            left: 20,
            top: 20,
            bottom: MediaQuery.of(context).viewInsets.bottom + 20,
          ),
          child: Directionality(
            textDirection: TextDirection.rtl,
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                Text(
                  'تأكيد انتهاء المعالجة',
                  style: Theme.of(context).textTheme.titleLarge,
                ),
                const SizedBox(height: 12),
                TextField(
                  controller: notesController,
                  maxLines: 3,
                  decoration: const InputDecoration(
                    labelText: 'ملاحظات إضافية',
                    hintText: 'اكتب ملاحظة مختصرة إن لزم',
                  ),
                ),
                const SizedBox(height: 16),
                ElevatedButton(
                  onPressed: () => Navigator.pop(context, true),
                  child: const Text('تمت المعالجة'),
                ),
              ],
            ),
          ),
        );
      },
    );

    if (confirmed != true) return;

    await ApiService.completeReport(
      token,
      assignmentId: assignment.assignmentId,
      notes: notesController.text,
    );

    if (!mounted) return;
    ScaffoldMessenger.of(context).showSnackBar(
      const SnackBar(content: Text('تمت معالجة البلاغ بنجاح')),
    );
    await _refresh();
  }

  Future<void> _startAssignment(DispatchAssignment assignment) async {
    final token = await SecureStorage.readToken();
    if (token == null || token.isEmpty) return;

    final notesController = TextEditingController();
    if (!mounted) return;

    final confirmed = await showModalBottomSheet<bool>(
      context: context,
      isScrollControlled: true,
      builder: (context) {
        return Padding(
          padding: EdgeInsets.only(
            right: 20,
            left: 20,
            top: 20,
            bottom: MediaQuery.of(context).viewInsets.bottom + 20,
          ),
          child: Directionality(
            textDirection: TextDirection.rtl,
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                Text(
                  'بدء معالجة البلاغ',
                  style: Theme.of(context).textTheme.titleLarge,
                ),
                const SizedBox(height: 12),
                TextField(
                  controller: notesController,
                  maxLines: 3,
                  decoration: const InputDecoration(
                    labelText: 'ملاحظات إضافية',
                    hintText: 'اكتب ملاحظة مختصرة إن لزم',
                  ),
                ),
                const SizedBox(height: 16),
                ElevatedButton(
                  onPressed: () => Navigator.pop(context, true),
                  child: const Text('بدء المعالجة'),
                ),
              ],
            ),
          ),
        );
      },
    );

    if (confirmed != true) return;

    await ApiService.startReportProcessing(
      token,
      assignmentId: assignment.assignmentId,
      notes: notesController.text,
    );

    if (!mounted) return;
    ScaffoldMessenger.of(context).showSnackBar(
      const SnackBar(content: Text('تم بدء المعالجة بنجاح')),
    );
    await _refresh();
  }

  bool _canStart(DispatchAssignment assignment) {
    return assignment.reportStatus.toLowerCase() == 'dispatched';
  }

  bool _canComplete(DispatchAssignment assignment) {
    return assignment.reportStatus.toLowerCase() != 'closed';
  }

  @override
  Widget build(BuildContext context) {
    return Directionality(
      textDirection: TextDirection.rtl,
      child: Scaffold(
        appBar: AppBar(title: const Text('مهام الاستجابة')),
        body: RefreshIndicator(
          onRefresh: _refresh,
          child: FutureBuilder<List<DispatchAssignment>>(
            future: _future,
            builder: (context, snapshot) {
              if (snapshot.connectionState == ConnectionState.waiting) {
                return const Center(child: CircularProgressIndicator());
              }

              if (snapshot.hasError) {
                return ListView(
                  children: [
                    Padding(
                      padding: const EdgeInsets.all(24),
                      child: Text(
                        'تعذر تحميل مهام الاستجابة:\n${snapshot.error}',
                        textAlign: TextAlign.center,
                      ),
                    ),
                  ],
                );
              }

              final items = snapshot.data ?? const <DispatchAssignment>[];
              if (items.isEmpty) {
                return ListView(
                  children: const [
                    Padding(
                      padding: EdgeInsets.all(24),
                      child: Text(
                        'لا توجد مهام استجابة نشطة حالياً.',
                        textAlign: TextAlign.center,
                      ),
                    ),
                  ],
                );
              }

              return ListView.separated(
                padding: const EdgeInsets.all(16),
                itemCount: items.length + 1,
                separatorBuilder: (_, __) => const SizedBox(height: 12),
                itemBuilder: (context, index) {
                  if (index == 0) {
                    return Container(
                      padding: const EdgeInsets.all(18),
                      decoration: BoxDecoration(
                        gradient: const LinearGradient(
                          colors: [Color(0xFF132F5F), Color(0xFF102544)],
                          begin: Alignment.topLeft,
                          end: Alignment.bottomRight,
                        ),
                        borderRadius: BorderRadius.circular(24),
                        boxShadow: [
                          BoxShadow(
                            color: Colors.black.withValues(alpha: 0.24),
                            blurRadius: 16,
                            offset: const Offset(0, 8),
                          )
                        ],
                      ),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            'مهام الاستجابة',
                            style: Theme.of(context)
                                .textTheme
                                .titleLarge
                                ?.copyWith(
                                  color: Colors.white,
                                  fontWeight: FontWeight.w700,
                                ),
                          ),
                          const SizedBox(height: 8),
                          Text(
                            '${items.length} بلاغ نشط',
                            style: Theme.of(context)
                                .textTheme
                                .bodyMedium
                                ?.copyWith(
                                  color: Colors.white70,
                                ),
                          ),
                          const SizedBox(height: 16),
                          Row(
                            children: [
                              Expanded(
                                child: _MiniDispatchBadge(
                                  label: 'جاري الانتظار',
                                  value:
                                      '${items.where((a) => a.reportStatus.toLowerCase() == "dispatched").length}',
                                  color: Colors.amber,
                                ),
                              ),
                              const SizedBox(width: 10),
                              Expanded(
                                child: _MiniDispatchBadge(
                                  label: 'قيد المعالجة',
                                  value:
                                      '${items.where((a) => a.reportStatus.toLowerCase() == "in_progress").length}',
                                  color: Colors.lightGreenAccent,
                                ),
                              ),
                            ],
                          ),
                        ],
                      ),
                    );
                  }

                  final assignment = items[index - 1];
                  final highlighted = widget.highlightId != null &&
                      (assignment.reportId == widget.highlightId ||
                          assignment.assignmentId == widget.highlightId);

                  return Container(
                    padding: const EdgeInsets.all(18),
                    decoration: BoxDecoration(
                      gradient: highlighted
                          ? const LinearGradient(
                              colors: [Color(0xFF133368), Color(0xFF0E2A54)],
                              begin: Alignment.topLeft,
                              end: Alignment.bottomRight,
                            )
                          : const LinearGradient(
                              colors: [Color(0xFF111B2E), Color(0xFF0D1528)],
                              begin: Alignment.topLeft,
                              end: Alignment.bottomRight,
                            ),
                      borderRadius: BorderRadius.circular(22),
                      border: Border.all(
                        color: highlighted
                            ? const Color(0xFF5AA9FF)
                            : Colors.white12,
                      ),
                    ),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Row(
                          children: [
                            Expanded(
                              child: Text(
                                assignment.title.isEmpty
                                    ? 'بلاغ بدون عنوان'
                                    : assignment.title,
                                style: const TextStyle(
                                  fontSize: 18,
                                  fontWeight: FontWeight.bold,
                                  color: Colors.white,
                                ),
                              ),
                            ),
                            const SizedBox(width: 10),
                            _StatusChip(status: assignment.reportStatus),
                          ],
                        ),
                        const SizedBox(height: 10),
                        Row(
                          children: [
                            _PriorityChip(priority: assignment.priority),
                            if (assignment.assignedAt != null &&
                                assignment.assignedAt!.isNotEmpty) ...[
                              const SizedBox(width: 10),
                              Container(
                                padding: const EdgeInsets.symmetric(
                                  horizontal: 12,
                                  vertical: 8,
                                ),
                                decoration: BoxDecoration(
                                  color: Colors.white12,
                                  borderRadius: BorderRadius.circular(14),
                                ),
                                child: Text(
                                  assignment.assignedAt!.split(' ').first,
                                  style: const TextStyle(
                                    color: Colors.white70,
                                    fontSize: 12,
                                  ),
                                ),
                              ),
                            ],
                          ],
                        ),
                        const SizedBox(height: 12),
                        Text(
                          assignment.description.isEmpty
                              ? 'لا يوجد وصف إضافي لهذا البلاغ.'
                              : assignment.description,
                          style: const TextStyle(color: Colors.white70),
                        ),
                        const SizedBox(height: 14),
                        _ReportImage(imageUrl: assignment.imageUrl),
                        const SizedBox(height: 14),
                        _InfoRow(
                          icon: Icons.place_outlined,
                          label: 'الموقع',
                          value: [
                            assignment.city,
                            assignment.streetName,
                            assignment.landmark,
                            assignment.address,
                          ]
                              .whereType<String>()
                              .where((e) => e.isNotEmpty)
                              .join(' - '),
                        ),
                        _InfoRow(
                          icon: Icons.social_distance_outlined,
                          label: 'المسافة',
                          value: assignment.distanceKm == null
                              ? 'غير محددة'
                              : '${assignment.distanceKm!.toStringAsFixed(2)} كم',
                        ),
                        _InfoRow(
                          icon: Icons.person_outline,
                          label: 'مقدّم البلاغ',
                          value: [
                            assignment.reporterName,
                            assignment.reporterPhone,
                          ]
                              .whereType<String>()
                              .where((e) => e.isNotEmpty)
                              .join(' - '),
                        ),
                        const SizedBox(height: 16),
                        if (_canStart(assignment) || _canComplete(assignment))
                          Row(
                            children: [
                              if (_canStart(assignment))
                                Expanded(
                                  child: ElevatedButton(
                                    onPressed: () =>
                                        _startAssignment(assignment),
                                    child: const Text('بدء المعالجة'),
                                  ),
                                ),
                              if (_canStart(assignment) &&
                                  _canComplete(assignment))
                                const SizedBox(width: 12),
                              if (_canComplete(assignment))
                                Expanded(
                                  child: OutlinedButton(
                                    onPressed: () =>
                                        _completeAssignment(assignment),
                                    style: OutlinedButton.styleFrom(
                                      side: const BorderSide(
                                          color: Colors.white24),
                                    ),
                                    child: const Text('تمت المعالجة'),
                                  ),
                                ),
                            ],
                          )
                        else
                          const _AssignmentNotice(
                            message:
                                'تمت معالجة هذا البلاغ سابقاً، لذلك زر المعالجة غير متاح هنا.',
                          ),
                      ],
                    ),
                  );
                },
              );
            },
          ),
        ),
      ),
    );
  }
}

class _ReportImage extends StatelessWidget {
  const _ReportImage({required this.imageUrl});

  final String? imageUrl;

  @override
  Widget build(BuildContext context) {
    if (imageUrl == null || imageUrl!.trim().isEmpty) {
      return const _ImagePlaceholder(
        label: 'لا توجد صورة مرفقة مع هذا البلاغ.',
      );
    }

    return ClipRRect(
      borderRadius: BorderRadius.circular(16),
      child: AspectRatio(
        aspectRatio: 16 / 9,
        child: Image.network(
          imageUrl!,
          fit: BoxFit.cover,
          loadingBuilder: (context, child, loadingProgress) {
            if (loadingProgress == null) {
              return child;
            }

            return Container(
              color: Colors.white.withValues(alpha: 0.06),
              alignment: Alignment.center,
              child: const CircularProgressIndicator(),
            );
          },
          errorBuilder: (context, error, stackTrace) {
            return const _ImagePlaceholder(
              label: 'تعذر تحميل صورة البلاغ من الخادم.',
            );
          },
        ),
      ),
    );
  }
}

class _ImagePlaceholder extends StatelessWidget {
  const _ImagePlaceholder({required this.label});

  final String label;

  @override
  Widget build(BuildContext context) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 18),
      decoration: BoxDecoration(
        color: Colors.white.withValues(alpha: 0.05),
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: Colors.white12),
      ),
      child: Row(
        children: [
          const Icon(Icons.image_not_supported_outlined, color: Colors.white70),
          const SizedBox(width: 10),
          Expanded(
            child: Text(
              label,
              style: const TextStyle(color: Colors.white70),
            ),
          ),
        ],
      ),
    );
  }
}

class _AssignmentNotice extends StatelessWidget {
  const _AssignmentNotice({required this.message});

  final String message;

  @override
  Widget build(BuildContext context) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
      decoration: BoxDecoration(
        color: const Color(0xFFFFF3CD).withValues(alpha: 0.12),
        borderRadius: BorderRadius.circular(14),
        border: Border.all(
          color: const Color(0xFFE3B341).withValues(alpha: 0.55),
        ),
      ),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Icon(
            Icons.info_outline,
            color: Color(0xFFE3B341),
            size: 20,
          ),
          const SizedBox(width: 10),
          Expanded(
            child: Text(
              message,
              style: const TextStyle(
                color: Color(0xFFFFE7A1),
                height: 1.4,
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _InfoRow extends StatelessWidget {
  final IconData icon;
  final String label;
  final String value;

  const _InfoRow({
    required this.icon,
    required this.label,
    required this.value,
  });

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 8),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Icon(icon, size: 18, color: Colors.white70),
          const SizedBox(width: 8),
          Text(
            '$label: ',
            style: const TextStyle(
              color: Colors.white,
              fontWeight: FontWeight.w600,
            ),
          ),
          Expanded(
            child: Text(
              value.isEmpty ? 'غير متوفر' : value,
              style: const TextStyle(color: Colors.white70),
            ),
          ),
        ],
      ),
    );
  }
}

class _PriorityChip extends StatelessWidget {
  const _PriorityChip({required this.priority});

  final String priority;

  @override
  Widget build(BuildContext context) {
    final normalized = priority.toLowerCase();
    final color = switch (normalized) {
      'urgent' => Colors.redAccent,
      'high' => Colors.orangeAccent,
      'medium' => Colors.amber,
      _ => Colors.blueGrey,
    };

    final label = switch (normalized) {
      'urgent' => 'عاجلة',
      'high' => 'مرتفعة',
      'medium' => 'متوسطة',
      'low' => 'منخفضة',
      _ => priority.isEmpty ? 'غير محددة' : priority,
    };

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.18),
        borderRadius: BorderRadius.circular(999),
        border: Border.all(color: color.withValues(alpha: 0.45)),
      ),
      child: Text(
        label,
        style: TextStyle(
          color: color,
          fontWeight: FontWeight.bold,
          fontSize: 12,
        ),
      ),
    );
  }
}

class _StatusChip extends StatelessWidget {
  final String status;

  const _StatusChip({required this.status});

  @override
  Widget build(BuildContext context) {
    final normalized = status.toLowerCase();
    final statusText = switch (normalized) {
      'submitted' => 'مرسل',
      'dispatched' => 'قيد الإرسال',
      'in_progress' => 'قيد المعالجة',
      'under_review' => 'قيد المراجعة',
      'closed' => 'مغلق',
      _ => status.isEmpty ? 'غير معروفة' : status,
    };
    final color = switch (normalized) {
      'submitted' => Colors.blueAccent,
      'dispatched' => Colors.lightBlueAccent,
      'in_progress' => Colors.lightGreenAccent,
      'under_review' => Colors.orangeAccent,
      'closed' => Colors.grey,
      _ => Colors.white70,
    };

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.18),
        borderRadius: BorderRadius.circular(999),
      ),
      child: Text(
        statusText,
        style: TextStyle(
          color: color,
          fontWeight: FontWeight.w700,
          fontSize: 12,
        ),
      ),
    );
  }
}

class _MiniDispatchBadge extends StatelessWidget {
  final String label;
  final String value;
  final Color color;

  const _MiniDispatchBadge({
    required this.label,
    required this.value,
    required this.color,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 14),
      decoration: BoxDecoration(
        color: Colors.white.withValues(alpha: 0.08),
        borderRadius: BorderRadius.circular(16),
      ),
      child: Row(
        children: [
          Container(
            width: 34,
            height: 34,
            decoration: BoxDecoration(
              color: color.withValues(alpha: 0.18),
              borderRadius: BorderRadius.circular(12),
            ),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  label,
                  style: const TextStyle(
                    color: Colors.white70,
                    fontSize: 12,
                  ),
                ),
                const SizedBox(height: 4),
                Text(
                  value,
                  style: const TextStyle(
                    color: Colors.white,
                    fontSize: 16,
                    fontWeight: FontWeight.w700,
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
