import 'package:flutter/material.dart';

import '../../models/dispatch_assignment.dart';
import '../../services/api_service.dart';
import '../../services/secure_storage.dart';

class DispatchAssignmentsPage extends StatefulWidget {
  const DispatchAssignmentsPage({super.key, this.highlightReportId});

  final int? highlightReportId;

  @override
  State<DispatchAssignmentsPage> createState() => _DispatchAssignmentsPageState();
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
      throw Exception('No token found');
    }

    return ApiService.getDispatchAssignments(token);
  }

  Future<void> _refresh() async {
    setState(() {
      _future = _loadAssignments();
    });
    await _future;
  }

  Future<void> _respond(DispatchAssignment assignment, String response) async {
    final token = await SecureStorage.readToken();
    if (token == null || token.isEmpty) return;

    await ApiService.respondToReportAssignment(
      token,
      reportId: assignment.reportId,
      response: response,
    );

    if (!mounted) return;
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(
          response == 'accept'
              ? 'Assignment accepted'
              : 'Assignment rejected',
        ),
      ),
    );
    await _refresh();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Dispatch Assignments')),
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
                    child: Text('Failed to load assignments: ${snapshot.error}'),
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
                    child: Text('No active dispatch assignments'),
                  ),
                ],
              );
            }

            return ListView.separated(
              padding: const EdgeInsets.all(16),
              itemCount: items.length,
              separatorBuilder: (_, __) => const SizedBox(height: 12),
              itemBuilder: (context, index) {
                final assignment = items[index];
                final highlighted =
                    widget.highlightReportId != null &&
                    assignment.reportId == widget.highlightReportId;

                return Container(
                  padding: const EdgeInsets.all(16),
                  decoration: BoxDecoration(
                    color: highlighted
                        ? const Color(0xFF17345E)
                        : Colors.white.withOpacity(0.08),
                    borderRadius: BorderRadius.circular(18),
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
                              assignment.title,
                              style: const TextStyle(
                                fontSize: 18,
                                fontWeight: FontWeight.bold,
                                color: Colors.white,
                              ),
                            ),
                          ),
                          _PriorityChip(priority: assignment.priority),
                        ],
                      ),
                      const SizedBox(height: 8),
                      Text(
                        assignment.description,
                        style: const TextStyle(color: Colors.white70),
                      ),
                      const SizedBox(height: 12),
                      Text(
                        'Location: ${assignment.city ?? '-'} ${assignment.streetName ?? ''} ${assignment.landmark ?? ''}',
                        style: const TextStyle(color: Colors.white),
                      ),
                      const SizedBox(height: 6),
                      Text(
                        'Distance: ${assignment.distanceKm?.toStringAsFixed(2) ?? '-'} km',
                        style: const TextStyle(color: Colors.white70),
                      ),
                      const SizedBox(height: 6),
                      Text(
                        'Reporter: ${assignment.reporterName ?? '-'} ${assignment.reporterPhone ?? ''}',
                        style: const TextStyle(color: Colors.white70),
                      ),
                      const SizedBox(height: 14),
                      Row(
                        children: [
                          Expanded(
                            child: ElevatedButton(
                              onPressed: () => _respond(assignment, 'accept'),
                              child: const Text('Accept'),
                            ),
                          ),
                          const SizedBox(width: 12),
                          Expanded(
                            child: OutlinedButton(
                              onPressed: () => _respond(assignment, 'reject'),
                              child: const Text('Reject'),
                            ),
                          ),
                        ],
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

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
      decoration: BoxDecoration(
        color: color.withOpacity(0.18),
        borderRadius: BorderRadius.circular(999),
        border: Border.all(color: color.withOpacity(0.5)),
      ),
      child: Text(
        priority.toUpperCase(),
        style: TextStyle(
          color: color,
          fontWeight: FontWeight.bold,
          fontSize: 12,
        ),
      ),
    );
  }
}
