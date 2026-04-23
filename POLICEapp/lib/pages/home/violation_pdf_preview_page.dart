import 'dart:io';
import 'dart:typed_data';

import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import 'package:printing/printing.dart';

class ViolationPdfPreviewPage extends StatelessWidget {
  final String? filePath;
  final String? pdfUrl;
  final int violationId;

  const ViolationPdfPreviewPage({
    super.key,
    this.filePath,
    this.pdfUrl,
    required this.violationId,
  });

  Future<Uint8List> _loadBytes() async {
    if (pdfUrl != null && pdfUrl!.trim().isNotEmpty) {
      final response = await http.get(Uri.parse(pdfUrl!));
      if (response.statusCode >= 200 && response.statusCode < 300) {
        return response.bodyBytes;
      }
      throw Exception(
          'Failed to load PDF from server (${response.statusCode})');
    }

    if (filePath != null && filePath!.trim().isNotEmpty) {
      return File(filePath!).readAsBytes();
    }

    throw Exception('No PDF source available');
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text('PDF #$violationId'),
      ),
      body: PdfPreview(
        canChangePageFormat: false,
        canChangeOrientation: false,
        canDebug: false,
        build: (_) => _loadBytes(),
      ),
    );
  }
}
