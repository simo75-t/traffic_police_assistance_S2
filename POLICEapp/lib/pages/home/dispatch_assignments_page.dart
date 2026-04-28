import 'package:flutter/material.dart';
import 'package:intl/intl.dart' show DateFormat;

import '../../core/police_theme.dart';
import '../../l10n/app_localizations.dart';
import '../../models/dispatch_assignment.dart';
import '../../services/api_service.dart';
import '../../services/secure_storage.dart';
import '../../widgets/app_button.dart';
import '../../widgets/app_card.dart';
import '../../widgets/empty_state_widget.dart';
import '../../widgets/loading_widget.dart';
import '../../widgets/status_badge.dart';

class DispatchAssignmentsPage extends StatefulWidget {
  const DispatchAssignmentsPage({super.key, this.highlightId});

  final int? highlightId;

  @override
  State<DispatchAssignmentsPage> createState() =>
      _DispatchAssignmentsPageState();
}

class _DispatchAssignmentsPageState extends State<DispatchAssignmentsPage> {
  static const _authRequiredCode = 'auth_required';

  Future<List<DispatchAssignment>> _future = Future.value(const []);

  @override
  void initState() {
    super.initState();
    _future = _loadAssignments();
  }

  Future<List<DispatchAssignment>> _loadAssignments() async {
    final token = await SecureStorage.readToken();
    if (token == null || token.isEmpty) {
      throw Exception(_authRequiredCode);
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

    final l10n = AppLocalizations.of(context);
    final confirmed = await showModalBottomSheet<bool>(
      context: context,
      isScrollControlled: true,
      builder: (context) {
        final isRtl = l10n.isRtl;
        return Padding(
          padding: EdgeInsets.only(
            right: 20,
            left: 20,
            top: 20,
            bottom: MediaQuery.of(context).viewInsets.bottom + 20,
          ),
          child: SafeArea(
            top: false,
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                Text(
                  l10n.dispatchCompleteSheetTitle,
                  style: Theme.of(context).textTheme.titleLarge,
                  textAlign: l10n.startTextAlign,
                ),
                const SizedBox(height: 8),
                Text(
                  l10n.dispatchCompleteSheetSubtitle,
                  style: Theme.of(context).textTheme.bodyMedium,
                  textAlign: l10n.startTextAlign,
                ),
                const SizedBox(height: 16),
                TextField(
                  controller: notesController,
                  maxLines: 3,
                  textAlign: isRtl ? TextAlign.right : TextAlign.left,
                  textDirection: l10n.textDirection,
                  decoration: InputDecoration(
                    labelText: l10n.dispatchNotesLabel,
                    hintText: l10n.dispatchNotesHint,
                    alignLabelWithHint: true,
                  ),
                ),
                const SizedBox(height: 16),
                AppButton(
                  label: l10n.dispatchCompleteConfirm,
                  onPressed: () => Navigator.pop(context, true),
                ),
                const SizedBox(height: 12),
                AppButton(
                  label: l10n.cancel,
                  onPressed: () => Navigator.pop(context, false),
                  variant: AppButtonVariant.secondary,
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
      SnackBar(content: Text(l10n.dispatchCompletedSuccess)),
    );
    await _refresh();
  }

  Future<void> _startAssignment(DispatchAssignment assignment) async {
    final token = await SecureStorage.readToken();
    if (token == null || token.isEmpty) return;

    final notesController = TextEditingController();
    if (!mounted) return;

    final l10n = AppLocalizations.of(context);
    final confirmed = await showModalBottomSheet<bool>(
      context: context,
      isScrollControlled: true,
      builder: (context) {
        final isRtl = l10n.isRtl;
        return Padding(
          padding: EdgeInsets.only(
            right: 20,
            left: 20,
            top: 20,
            bottom: MediaQuery.of(context).viewInsets.bottom + 20,
          ),
          child: SafeArea(
            top: false,
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                Text(
                  l10n.dispatchStartSheetTitle,
                  style: Theme.of(context).textTheme.titleLarge,
                  textAlign: l10n.startTextAlign,
                ),
                const SizedBox(height: 8),
                Text(
                  l10n.dispatchStartSheetSubtitle,
                  style: Theme.of(context).textTheme.bodyMedium,
                  textAlign: l10n.startTextAlign,
                ),
                const SizedBox(height: 16),
                TextField(
                  controller: notesController,
                  maxLines: 3,
                  textAlign: isRtl ? TextAlign.right : TextAlign.left,
                  textDirection: l10n.textDirection,
                  decoration: InputDecoration(
                    labelText: l10n.dispatchNotesLabel,
                    hintText: l10n.dispatchNotesHint,
                    alignLabelWithHint: true,
                  ),
                ),
                const SizedBox(height: 16),
                AppButton(
                  label: l10n.dispatchStartConfirm,
                  onPressed: () => Navigator.pop(context, true),
                ),
                const SizedBox(height: 12),
                AppButton(
                  label: l10n.cancel,
                  onPressed: () => Navigator.pop(context, false),
                  variant: AppButtonVariant.secondary,
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
      SnackBar(content: Text(l10n.dispatchStartedSuccess)),
    );
    await _refresh();
  }

  bool _canStart(DispatchAssignment assignment) {
    return assignment.reportStatus.toLowerCase() == 'dispatched';
  }

  bool _canComplete(DispatchAssignment assignment) {
    return assignment.reportStatus.toLowerCase() != 'closed';
  }

  String _formatAssignmentDate(AppLocalizations l10n, String? rawValue) {
    if (rawValue == null || rawValue.trim().isEmpty) {
      return l10n.notSpecified;
    }

    final parsed = DateTime.tryParse(rawValue);
    if (parsed == null) {
      return rawValue;
    }

    return DateFormat('yyyy/MM/dd - HH:mm').format(parsed.toLocal());
  }

  String _locationText(AppLocalizations l10n, DispatchAssignment assignment) {
    final parts = [
      assignment.city,
      assignment.streetName,
      assignment.landmark,
      assignment.address,
    ].whereType<String>().map((value) => value.trim()).where((e) => e.isNotEmpty);

    return parts.isEmpty ? l10n.notAvailable : parts.join(' - ');
  }

  String _reporterText(AppLocalizations l10n, DispatchAssignment assignment) {
    final parts = [
      assignment.reporterName,
      assignment.reporterPhone,
    ].whereType<String>().map((value) => value.trim()).where((e) => e.isNotEmpty);

    return parts.isEmpty ? l10n.notAvailable : parts.join(' - ');
  }

  String _localizedError(AppLocalizations l10n, Object error) {
    final text = error.toString();
    if (text.contains(_authRequiredCode)) {
      return l10n.dispatchAuthRequired;
    }
    return text;
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);

    return Scaffold(
      appBar: AppBar(title: Text(l10n.dispatchPageTitle)),
      body: RefreshIndicator(
        onRefresh: _refresh,
        child: FutureBuilder<List<DispatchAssignment>>(
          future: _future,
          builder: (context, snapshot) {
            if (snapshot.connectionState == ConnectionState.waiting) {
              return LoadingWidget(label: l10n.dispatchLoading);
            }

            if (snapshot.hasError) {
              final localizedError = _localizedError(l10n, snapshot.error!);
              return ListView(
                padding: const EdgeInsets.all(16),
                children: [
                  EmptyStateWidget(
                    title: l10n.dispatchErrorTitle,
                    subtitle: l10n.dispatchErrorSubtitle(localizedError),
                    icon: Icons.error_outline,
                    actionLabel: l10n.retry,
                    onAction: _refresh,
                  ),
                ],
              );
            }

            final items = snapshot.data ?? const <DispatchAssignment>[];
            if (items.isEmpty) {
              return ListView(
                padding: const EdgeInsets.all(16),
                children: [
                  EmptyStateWidget(
                    title: l10n.dispatchEmptyTitle,
                    subtitle: l10n.dispatchEmptySubtitle,
                    icon: Icons.assignment_outlined,
                  ),
                ],
              );
            }

            final dispatchedCount = items
                .where((a) => a.reportStatus.toLowerCase() == 'dispatched')
                .length;
            final inProgressCount = items
                .where((a) => a.reportStatus.toLowerCase() == 'in_progress')
                .length;

            return ListView.separated(
              padding: const EdgeInsets.all(16),
              itemCount: items.length + 1,
              separatorBuilder: (_, __) => const SizedBox(height: 12),
              itemBuilder: (context, index) {
                if (index == 0) {
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
                          begin: Alignment.topRight,
                          end: Alignment.bottomLeft,
                        ),
                      ),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            l10n.dispatchHeaderTitle,
                            style: Theme.of(context)
                                .textTheme
                                .titleLarge
                                ?.copyWith(color: Colors.white),
                            textAlign: l10n.startTextAlign,
                          ),
                          const SizedBox(height: 8),
                          Text(
                            l10n.dispatchHeaderSummary(items.length),
                            style: Theme.of(context)
                                .textTheme
                                .bodyMedium
                                ?.copyWith(color: Colors.white70),
                            textAlign: l10n.startTextAlign,
                          ),
                          const SizedBox(height: 16),
                          Row(
                            children: [
                              Expanded(
                                child: _MiniDispatchBadge(
                                  label: l10n.dispatchWaitingToStart,
                                  value: '$dispatchedCount',
                                  color: PoliceTheme.warning,
                                ),
                              ),
                              const SizedBox(width: 10),
                              Expanded(
                                child: _MiniDispatchBadge(
                                  label: l10n.dispatchInProgress,
                                  value: '$inProgressCount',
                                  color: PoliceTheme.success,
                                ),
                              ),
                            ],
                          ),
                        ],
                      ),
                    ),
                  );
                }

                final assignment = items[index - 1];
                final highlighted = widget.highlightId != null &&
                    (assignment.reportId == widget.highlightId ||
                        assignment.assignmentId == widget.highlightId);

                return AppCard(
                  backgroundColor:
                      highlighted ? const Color(0xFFEAF1FB) : Colors.white,
                  borderColor: highlighted
                      ? PoliceTheme.secondary
                      : const Color(0xFFE2E8F0),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      LayoutBuilder(
                        builder: (context, constraints) {
                          final compact = constraints.maxWidth < 360;
                          final title = assignment.title.isEmpty
                              ? l10n.dispatchUntitled
                              : assignment.title;
                          if (compact) {
                            return Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(
                                  title,
                                  style: const TextStyle(
                                    fontSize: 18,
                                    fontWeight: FontWeight.bold,
                                    color: PoliceTheme.textPrimary,
                                  ),
                                  textAlign: l10n.startTextAlign,
                                ),
                                const SizedBox(height: 10),
                                _StatusChip(status: assignment.reportStatus),
                              ],
                            );
                          }

                          return Row(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Expanded(
                                child: Text(
                                  title,
                                  style: const TextStyle(
                                    fontSize: 18,
                                    fontWeight: FontWeight.bold,
                                    color: PoliceTheme.textPrimary,
                                  ),
                                  textAlign: l10n.startTextAlign,
                                ),
                              ),
                              const SizedBox(width: 10),
                              _StatusChip(status: assignment.reportStatus),
                            ],
                          );
                        },
                      ),
                      const SizedBox(height: 10),
                      Wrap(
                        spacing: 10,
                        runSpacing: 10,
                        children: [
                          _PriorityChip(priority: assignment.priority),
                          _MetaBadge(
                            icon: Icons.schedule_outlined,
                            label: l10n.dispatchAssignedAt(
                              _formatAssignmentDate(l10n, assignment.assignedAt),
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 12),
                      Text(
                        assignment.description.isEmpty
                            ? l10n.dispatchNoDetails
                            : assignment.description,
                        style: const TextStyle(
                          color: PoliceTheme.textSecondary,
                          height: 1.5,
                        ),
                        textAlign: l10n.startTextAlign,
                      ),
                      const SizedBox(height: 14),
                      _ReportImage(imageUrl: assignment.imageUrl),
                      const SizedBox(height: 14),
                      _InfoRow(
                        icon: Icons.place_outlined,
                        label: l10n.dispatchLocation,
                        value: _locationText(l10n, assignment),
                      ),
                      _InfoRow(
                        icon: Icons.social_distance_outlined,
                        label: l10n.dispatchDistance,
                        value: assignment.distanceKm == null
                            ? l10n.notSpecified
                            : l10n.dispatchDistanceKm(
                                assignment.distanceKm!.toStringAsFixed(2),
                              ),
                      ),
                      _InfoRow(
                        icon: Icons.person_outline,
                        label: l10n.dispatchReporterData,
                        value: _reporterText(l10n, assignment),
                      ),
                      const SizedBox(height: 16),
                      _AssignmentActions(
                        canStart: _canStart(assignment),
                        canComplete: _canComplete(assignment),
                        onStart: () => _startAssignment(assignment),
                        onComplete: () => _completeAssignment(assignment),
                      ),
                      if (!_canStart(assignment) && !_canComplete(assignment))
                        Padding(
                          padding: const EdgeInsets.only(top: 16),
                          child: _AssignmentNotice(
                            message: l10n.dispatchClosedNotice,
                          ),
                        ),
                    ],
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

class _AssignmentActions extends StatelessWidget {
  const _AssignmentActions({
    required this.canStart,
    required this.canComplete,
    required this.onStart,
    required this.onComplete,
  });

  final bool canStart;
  final bool canComplete;
  final VoidCallback onStart;
  final VoidCallback onComplete;

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);

    if (!canStart && !canComplete) {
      return const SizedBox.shrink();
    }

    return LayoutBuilder(
      builder: (context, constraints) {
        final vertical = constraints.maxWidth < 420;
        final children = <Widget>[
          if (canStart)
            AppButton(
              label: l10n.dispatchStartAction,
              onPressed: onStart,
              expanded: !vertical,
            ),
          if (canComplete)
            AppButton(
              label: l10n.dispatchCompleteAction,
              onPressed: onComplete,
              variant: AppButtonVariant.secondary,
              expanded: !vertical,
            ),
        ];

        if (vertical) {
          return Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              for (var i = 0; i < children.length; i++) ...[
                children[i],
                if (i != children.length - 1) const SizedBox(height: 12),
              ],
            ],
          );
        }

        return Row(
          children: [
            if (canStart) Expanded(child: children[0]),
            if (canStart && canComplete) const SizedBox(width: 12),
            if (canComplete)
              Expanded(child: children[canStart ? 1 : 0]),
          ],
        );
      },
    );
  }
}

class _ReportImage extends StatelessWidget {
  const _ReportImage({required this.imageUrl});

  final String? imageUrl;

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);

    if (imageUrl == null || imageUrl!.trim().isEmpty) {
      return _ImagePlaceholder(
        label: l10n.dispatchNoImage,
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
              color: Colors.grey.shade100,
              alignment: Alignment.center,
              child: const CircularProgressIndicator(),
            );
          },
          errorBuilder: (context, error, stackTrace) {
            return _ImagePlaceholder(
              label: l10n.dispatchImageLoadError,
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
    final l10n = AppLocalizations.of(context);

    return Container(
      width: double.infinity,
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 18),
      decoration: BoxDecoration(
        color: const Color(0xFFF8FAFC),
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: const Color(0xFFE2E8F0)),
      ),
      child: Row(
        children: [
          const Icon(
            Icons.image_not_supported_outlined,
            color: PoliceTheme.textSecondary,
          ),
          const SizedBox(width: 10),
          Expanded(
            child: Text(
              label,
              style: const TextStyle(color: PoliceTheme.textSecondary),
              textAlign: l10n.startTextAlign,
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
    final l10n = AppLocalizations.of(context);

    return Container(
      width: double.infinity,
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
      decoration: BoxDecoration(
        color: PoliceTheme.warning.withValues(alpha: 0.10),
        borderRadius: BorderRadius.circular(14),
        border: Border.all(
          color: PoliceTheme.warning.withValues(alpha: 0.32),
        ),
      ),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Icon(
            Icons.info_outline,
            color: PoliceTheme.warning,
            size: 20,
          ),
          const SizedBox(width: 10),
          Expanded(
            child: Text(
              message,
              style: const TextStyle(
                color: PoliceTheme.textPrimary,
                height: 1.4,
              ),
              textAlign: l10n.startTextAlign,
            ),
          ),
        ],
      ),
    );
  }
}

class _InfoRow extends StatelessWidget {
  const _InfoRow({
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
      padding: const EdgeInsets.only(bottom: 8),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const SizedBox(width: 2),
          Icon(icon, size: 18, color: PoliceTheme.textSecondary),
          const SizedBox(width: 8),
          Expanded(
            child: RichText(
              textAlign: l10n.startTextAlign,
              text: TextSpan(
                style: DefaultTextStyle.of(context).style,
                children: [
                  TextSpan(
                    text: '$label: ',
                    style: const TextStyle(
                      color: PoliceTheme.textPrimary,
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                  TextSpan(
                    text: value.isEmpty ? l10n.notAvailable : value,
                    style: const TextStyle(color: PoliceTheme.textSecondary),
                  ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _MetaBadge extends StatelessWidget {
  const _MetaBadge({
    required this.icon,
    required this.label,
  });

  final IconData icon;
  final String label;

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
      decoration: BoxDecoration(
        color: const Color(0xFFF8FAFC),
        borderRadius: BorderRadius.circular(14),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(icon, size: 16, color: PoliceTheme.textSecondary),
          const SizedBox(width: 8),
          Flexible(
            child: Text(
              label,
              style: const TextStyle(
                color: PoliceTheme.textSecondary,
                fontSize: 12,
              ),
              textAlign: l10n.startTextAlign,
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
    final l10n = AppLocalizations.of(context);
    final normalized = priority.toLowerCase();
    final color = switch (normalized) {
      'urgent' => PoliceTheme.error,
      'high' => PoliceTheme.warning,
      'medium' => PoliceTheme.accent,
      _ => const Color(0xFF64748B),
    };

    return StatusBadge(
      label: l10n.dispatchPriorityLabel(l10n.dispatchPriority(priority)),
      color: color,
    );
  }
}

class _StatusChip extends StatelessWidget {
  const _StatusChip({required this.status});

  final String status;

  @override
  Widget build(BuildContext context) {
    final normalized = status.toLowerCase();
    final color = switch (normalized) {
      'submitted' => PoliceTheme.processing,
      'dispatched' => PoliceTheme.secondary,
      'in_progress' => PoliceTheme.success,
      'under_review' => PoliceTheme.warning,
      'closed' => const Color(0xFF64748B),
      _ => const Color(0xFF64748B),
    };

    return StatusBadge(
      label: AppLocalizations.of(context).dispatchStatus(status),
      color: color,
    );
  }
}

class _MiniDispatchBadge extends StatelessWidget {
  const _MiniDispatchBadge({
    required this.label,
    required this.value,
    required this.color,
  });

  final String label;
  final String value;
  final Color color;

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 14),
      decoration: BoxDecoration(
        color: Colors.white.withValues(alpha: 0.12),
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
            child: Icon(
              Icons.assignment_outlined,
              color: color,
              size: 18,
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
                  textAlign: l10n.startTextAlign,
                ),
                const SizedBox(height: 4),
                Text(
                  value,
                  style: const TextStyle(
                    color: Colors.white,
                    fontSize: 16,
                    fontWeight: FontWeight.w700,
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
