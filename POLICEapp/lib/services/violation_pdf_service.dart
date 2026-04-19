import 'dart:io';

import 'package:flutter/services.dart' show rootBundle;
import 'package:intl/intl.dart';
import 'package:path/path.dart' as p;
import 'package:path_provider/path_provider.dart';
import 'package:pdf/pdf.dart';
import 'package:pdf/widgets.dart' as pw;

import '../models/violation.dart';
import '../utils/data_utils.dart';
import 'api_service.dart';
import 'secure_storage.dart';

class ViolationPdfService {
  static pw.Font? _regularFont;
  static pw.Font? _boldFont;

  static Future<File> ensurePdf(
    Violation violation, {
    bool force = false,
  }) async {
    final file = await _targetFile(violation.id);
    if (!force && await file.exists()) {
      return file;
    }

    await _loadFonts();
    final officerName = await _resolveOfficerName();
    final doc = _buildDocument(violation, officerName);
    await file.writeAsBytes(await doc.save(), flush: true);
    return file;
  }

  static Future<File> _targetFile(int violationId) async {
    final dir = await getApplicationDocumentsDirectory();
    final pdfDir = Directory(p.join(dir.path, 'violation_pdfs'));
    if (!await pdfDir.exists()) {
      await pdfDir.create(recursive: true);
    }
    return File(p.join(pdfDir.path, 'violation_$violationId.pdf'));
  }

  static Future<void> _loadFonts() async {
    if (_regularFont != null && _boldFont != null) {
      return;
    }

    final regularData = await rootBundle.load('assets/fonts/arial.ttf');
    final boldData = await rootBundle.load('assets/fonts/arialbd.ttf');
    _regularFont = pw.Font.ttf(regularData);
    _boldFont = pw.Font.ttf(boldData);
  }

  static Future<String> _resolveOfficerName() async {
    try {
      final token = await SecureStorage.readToken();
      if (token == null || token.trim().isEmpty) {
        return '-';
      }
      final profile = await ApiService.getProfile(token);
      final name = profile.name.trim();
      return name.isEmpty ? '-' : name;
    } catch (_) {
      return '-';
    }
  }

  static pw.Document _buildDocument(Violation violation, String officerName) {
    final doc = pw.Document();

    doc.addPage(
      pw.Page(
        pageFormat: PdfPageFormat.a4.landscape,
        margin: const pw.EdgeInsets.fromLTRB(12, 12, 12, 12),
        textDirection: pw.TextDirection.rtl,
        theme: pw.ThemeData.withFont(
          base: _regularFont!,
          bold: _boldFont!,
        ),
        build: (_) => _buildSheet(violation, officerName),
      ),
    );

    return doc;
  }

  static pw.Widget _buildSheet(Violation violation, String officerName) {
    return pw.Row(
      crossAxisAlignment: pw.CrossAxisAlignment.stretch,
      children: [
        pw.Expanded(
          flex: 4,
          child: _buildMainSlip(violation, officerName),
        ),
        pw.Container(width: 14),
        pw.Container(width: 1, color: PdfColors.grey700),
        pw.Container(width: 14),
        pw.Expanded(
          flex: 1,
          child: _buildStubSlip(violation, officerName),
        ),
      ],
    );
  }

  static pw.Widget _buildMainSlip(Violation violation, String officerName) {
    final plate = _pick([
      violation.plateNumber,
    ]);
    final owner = _pick([
      violation.ownerName,
    ]);
    final model = _pick([
      violation.vehicleModelName,
    ]);
    final color = _pick([
      violation.vehicleColorName,
    ]);
    final city = _pick([violation.locationCityName]);
    final street = _pick([violation.locationStreetName]);
    final landmark = _pick([violation.locationLandmark]);
    final address = _pick([violation.locationAddress]);
    final violationType = _pick([violation.violationType?['name']]);
    final description = _pick([violation.description]);
    final fineAmount = _pick([violation.fineAmount]);
    final latitude = _pick([violation.locationLatitude]);
    final longitude = _pick([violation.locationLongitude]);
    final createdAt = _formatDate(violation.occurredAt, violation.createdAt);

    return pw.Container(
      decoration: pw.BoxDecoration(
        border: pw.Border.all(color: PdfColors.black, width: 1.2),
      ),
      padding: const pw.EdgeInsets.all(18),
      child: pw.Column(
        crossAxisAlignment: pw.CrossAxisAlignment.stretch,
        children: [
          pw.Row(
            mainAxisAlignment: pw.MainAxisAlignment.spaceBetween,
            children: [
              pw.Text(
                'قيادة شرطة المرور',
                style: pw.TextStyle(
                  fontSize: 16,
                  fontWeight: pw.FontWeight.bold,
                ),
              ),
              pw.Column(
                crossAxisAlignment: pw.CrossAxisAlignment.end,
                children: [
                  pw.Text(
                    'ضبط مخالفة سير',
                    style: pw.TextStyle(
                      fontSize: 24,
                      fontWeight: pw.FontWeight.bold,
                    ),
                  ),
                  pw.SizedBox(height: 2),
                  pw.Text(
                    'رقم ${violation.id}',
                    style: pw.TextStyle(
                      fontSize: 19,
                      fontWeight: pw.FontWeight.bold,
                    ),
                  ),
                ],
              ),
            ],
          ),
          pw.SizedBox(height: 12),
          _formLine(
            'في هذا اليوم',
            createdAt,
            'وبناءً على الضبط المنظم بحق المركبة ذات الرقم',
            plate,
          ),
          _formLine('اسم المالك', owner, 'نوع المركبة', model),
          _formLine('لون المركبة', color, 'نوع المخالفة', violationType),
          _formLine('قيمة الغرامة', fineAmount, 'المحافظة', city),
          _formLine('الشارع', street, 'أقرب دلالة', landmark),
          _singleLine('العنوان التفصيلي', address),
          _singleLine(
              'الإحداثيات', 'خط العرض $latitude    خط الطول $longitude'),
          pw.SizedBox(height: 10),
          _boxedText(
            label: 'وصف المخالفة',
            value: description,
            minHeight: 88,
          ),
          pw.SizedBox(height: 12),
          pw.Text(
            'لذلك نظمنا هذا الضبط استنادًا إلى البيانات المدخلة في النظام، وبعد الاطلاع على الوقائع المذكورة أعلاه.',
            textAlign: pw.TextAlign.right,
            style: const pw.TextStyle(fontSize: 13),
          ),
          pw.Spacer(),
          pw.Row(
            children: [
              pw.Expanded(child: _signature('توقيع المخالف', '-')),
              pw.SizedBox(width: 24),
              pw.Expanded(
                child: _signature('اسم وتوقيع منظم الضبط', officerName),
              ),
            ],
          ),
        ],
      ),
    );
  }

  static pw.Widget _buildStubSlip(Violation violation, String officerName) {
    final plate = _pick([
      violation.plateNumber,
    ]);
    final owner = _pick([
      violation.ownerName,
    ]);
    final violationType = _pick([violation.violationType?['name']]);
    final fineAmount = _pick([violation.fineAmount]);
    final createdAt = _formatDate(violation.occurredAt, violation.createdAt);
    final city = _pick([violation.locationCityName]);
    final street = _pick([violation.locationStreetName]);

    return pw.Container(
      padding: const pw.EdgeInsets.all(14),
      decoration: pw.BoxDecoration(
        border: pw.Border.all(color: PdfColors.black, width: 1.1),
      ),
      child: pw.Column(
        crossAxisAlignment: pw.CrossAxisAlignment.stretch,
        children: [
          pw.Text(
            'قيادة شرطة المرور',
            textAlign: pw.TextAlign.right,
            style: pw.TextStyle(
              fontSize: 13,
              fontWeight: pw.FontWeight.bold,
            ),
          ),
          pw.SizedBox(height: 4),
          pw.Text(
            'ضبط مخالفة سير',
            textAlign: pw.TextAlign.right,
            style: pw.TextStyle(
              fontSize: 18,
              fontWeight: pw.FontWeight.bold,
            ),
          ),
          pw.SizedBox(height: 4),
          pw.Text(
            'رقم ${violation.id}',
            textAlign: pw.TextAlign.right,
            style: pw.TextStyle(
              fontSize: 15,
              fontWeight: pw.FontWeight.bold,
            ),
          ),
          pw.SizedBox(height: 12),
          _singleLine('اسم المخالف', owner),
          _singleLine('رقم اللوحة', plate),
          _singleLine('نوع المخالفة', violationType),
          _singleLine('قيمة الغرامة', fineAmount),
          _singleLine(
            'المكان',
            city == '-' && street == '-' ? '-' : '$city - $street',
          ),
          _singleLine('التاريخ', createdAt),
          pw.Spacer(),
          _signature('اسم وتوقيع منظم الضبط', officerName),
        ],
      ),
    );
  }

  static pw.Widget _formLine(
    String rightLabel,
    String rightValue,
    String leftLabel,
    String leftValue,
  ) {
    return pw.Padding(
      padding: const pw.EdgeInsets.only(bottom: 8),
      child: pw.Row(
        children: [
          pw.Expanded(child: _inlineField(rightLabel, rightValue)),
          pw.SizedBox(width: 10),
          pw.Expanded(child: _inlineField(leftLabel, leftValue)),
        ],
      ),
    );
  }

  static pw.Widget _singleLine(String label, String value) {
    return pw.Padding(
      padding: const pw.EdgeInsets.only(bottom: 8),
      child: _inlineField(label, value),
    );
  }

  static pw.Widget _inlineField(String label, String value) {
    return pw.Row(
      crossAxisAlignment: pw.CrossAxisAlignment.end,
      children: [
        pw.Text(
          '$label: ',
          style: pw.TextStyle(
            fontSize: 13,
            fontWeight: pw.FontWeight.bold,
          ),
        ),
        pw.Expanded(
          child: pw.Container(
            padding: const pw.EdgeInsets.only(bottom: 2),
            decoration: const pw.BoxDecoration(
              border: pw.Border(
                bottom: pw.BorderSide(
                  color: PdfColors.black,
                  width: 0.8,
                ),
              ),
            ),
            child: pw.Text(
              value,
              textAlign: pw.TextAlign.right,
              style: const pw.TextStyle(fontSize: 13),
            ),
          ),
        ),
      ],
    );
  }

  static pw.Widget _boxedText({
    required String label,
    required String value,
    double minHeight = 90,
  }) {
    return pw.Column(
      crossAxisAlignment: pw.CrossAxisAlignment.end,
      children: [
        pw.Text(
          '$label:',
          style: pw.TextStyle(
            fontSize: 13,
            fontWeight: pw.FontWeight.bold,
          ),
        ),
        pw.SizedBox(height: 4),
        pw.Container(
          width: double.infinity,
          constraints: pw.BoxConstraints(minHeight: minHeight),
          padding: const pw.EdgeInsets.all(10),
          decoration: pw.BoxDecoration(
            border: pw.Border.all(color: PdfColors.black, width: 0.8),
          ),
          child: pw.Text(
            value,
            textAlign: pw.TextAlign.right,
            style: const pw.TextStyle(fontSize: 13),
          ),
        ),
      ],
    );
  }

  static pw.Widget _signature(String label, String value) {
    return pw.Column(
      crossAxisAlignment: pw.CrossAxisAlignment.end,
      children: [
        pw.Text(
          label,
          style: pw.TextStyle(
            fontSize: 13,
            fontWeight: pw.FontWeight.bold,
          ),
        ),
        pw.SizedBox(height: 10),
        pw.Container(
          width: double.infinity,
          padding: const pw.EdgeInsets.only(bottom: 2),
          decoration: const pw.BoxDecoration(
            border: pw.Border(
              bottom: pw.BorderSide(
                color: PdfColors.black,
                width: 0.8,
              ),
            ),
          ),
          child: pw.Text(
            value,
            textAlign: pw.TextAlign.right,
            style: const pw.TextStyle(fontSize: 13),
          ),
        ),
      ],
    );
  }

  static String _pick(List<dynamic> values) {
    for (final value in values) {
      if (value == null) {
        continue;
      }
      final text = value.toString().trim();
      if (text.isNotEmpty && text.toLowerCase() != 'null') {
        return text;
      }
    }
    return '-';
  }

  static String _formatDate(String? occurredAt, String? createdAt) {
    final dt = AppDateUtils.violationDate(
      occurredAt: occurredAt,
      createdAt: createdAt,
    );
    if (dt == null) {
      return '-';
    }
    return DateFormat('yyyy/MM/dd - HH:mm').format(dt.toLocal());
  }
}
