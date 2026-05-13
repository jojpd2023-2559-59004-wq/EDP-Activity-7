<?php
/**
 * Excel Export Module - POSIS Academic EDP
 * 
 * Generates an MHTML (.xls) file with embedded logo image.
 * MHTML bundles images as MIME parts which Excel supports natively.
 *
 *   - Sheet 1: Report data with company logo, header, data table, signature placeholder
 *   - Sheet 2: Chart visualization with bar chart and summary statistics
 */

session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'Database.php';
$database = new Database();
$db = $database->getConnection();

$reportType = $_GET['report'] ?? '';
$userName = $_SESSION['user_name'] ?? 'System Administrator';

// ── Fetch report data ────────────────────────────────────────────────────────
$reportData = [];
$reportHeaders = [];
$reportTitle = '';
$chartTitle = '';
$chartLabels = [];
$chartValues = [];
$chartSeriesName = '';

if ($reportType === 'enrollment') {
    $reportTitle = 'Student Enrollment Report';
    $reportHeaders = ['Enroll ID', 'Student ID', 'Student Name', 'Course ID', 'Course Title', 'Credits', 'Grade'];
    $reportData = $db->query("
        SELECT e.EnrollID, e.StudentID, CONCAT(s.FirstName, ' ', s.LastName) AS StudentName,
               e.CourseID, c.Title AS CourseTitle, c.Credits, e.GradeNum
        FROM enrollment e
        LEFT JOIN student s ON e.StudentID = s.StudentID
        LEFT JOIN course c ON e.CourseID = c.CourseID
        ORDER BY e.EnrollID ASC
    ")->fetchAll(PDO::FETCH_ASSOC);

    $chartTitle = 'Enrollments Per Course';
    $chartSeriesName = 'Number of Students';
    $chartRows = $db->query("
        SELECT c.Title AS CourseName, COUNT(e.EnrollID) AS EnrollCount
        FROM course c LEFT JOIN enrollment e ON c.CourseID = e.CourseID
        GROUP BY c.CourseID, c.Title ORDER BY EnrollCount DESC
    ")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($chartRows as $cr) {
        $chartLabels[] = $cr['CourseName'];
        $chartValues[] = intval($cr['EnrollCount']);
    }
} elseif ($reportType === 'grades') {
    $reportTitle = 'Student Grades & Performance Report';
    $reportHeaders = ['Student ID', 'Student Name', 'Course', 'Grade (4.0)', 'Percentage (%)', 'Performance'];
    $rows = $db->query("
        SELECT e.StudentID, CONCAT(s.FirstName, ' ', s.LastName) AS StudentName,
               c.Title AS CourseTitle, e.GradeNum,
               ROUND((e.GradeNum / 4.0) * 100, 1) AS Percentage
        FROM enrollment e
        LEFT JOIN student s ON e.StudentID = s.StudentID
        LEFT JOIN course c ON e.CourseID = c.CourseID
        ORDER BY s.LastName, s.FirstName
    ")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as &$r) {
        $g = floatval($r['GradeNum']);
        $r['Performance'] = $g >= 3.5 ? 'Excellent' : ($g >= 2.5 ? 'Good' : 'Needs Improvement');
    }
    $reportData = $rows;

    $chartTitle = 'Average Grade Per Student';
    $chartSeriesName = 'Average Grade';
    $chartRows = $db->query("
        SELECT CONCAT(s.FirstName, ' ', s.LastName) AS StudentName, ROUND(AVG(e.GradeNum), 2) AS AvgGrade
        FROM enrollment e LEFT JOIN student s ON e.StudentID = s.StudentID
        GROUP BY e.StudentID, s.FirstName, s.LastName ORDER BY AvgGrade DESC
    ")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($chartRows as $cr) {
        $chartLabels[] = $cr['StudentName'];
        $chartValues[] = floatval($cr['AvgGrade']);
    }
} elseif ($reportType === 'course_assignments') {
    $reportTitle = 'Course-Professor Assignment Report';
    $reportHeaders = ['Course ID', 'Course Title', 'Credits', 'Professor ID', 'Professor Name', 'Professor Email', 'Status'];
    $rows = $db->query("
        SELECT c.CourseID, c.Title, c.Credits, c.ProfID,
               CONCAT(p.FirstName, ' ', p.LastName) AS ProfName, p.Email AS ProfEmail
        FROM course c LEFT JOIN professor p ON c.ProfID = p.ProfID ORDER BY c.CourseID ASC
    ")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as &$r) {
        $r['Status'] = $r['ProfID'] ? 'Assigned' : 'Unassigned';
        if (!$r['ProfName']) { $r['ProfName'] = 'N/A'; $r['ProfEmail'] = 'N/A'; }
    }
    $reportData = $rows;

    $chartTitle = 'Course Credits Distribution';
    $chartSeriesName = 'Credits';
    foreach ($rows as $cr) {
        $chartLabels[] = $cr['Title'];
        $chartValues[] = intval($cr['Credits']);
    }
} else {
    header("Location: report_generator.php");
    exit();
}

// ── Prepare logo: resize with GD and encode ──────────────────────────────────
$logoBase64 = '';
$logoPath = __DIR__ . '/logo.png';
$logoAspectRatio = 1;
$logoPrimaryWidth = 140;
$logoSecondaryWidth = 90;
$logoPrimaryHeight = 140;
$logoSecondaryHeight = 90;
$logoContentId = 'logo-posis@posis.local';
$logoContentLocation = 'file:///C:/logo_thumb.png';
if (file_exists($logoPath)) {
    $logoMeta = @getimagesize($logoPath);
    if ($logoMeta && !empty($logoMeta[0]) && !empty($logoMeta[1])) {
        $logoAspectRatio = $logoMeta[1] / $logoMeta[0];
        $logoPrimaryHeight = max(1, (int) round($logoPrimaryWidth * $logoAspectRatio));
        $logoSecondaryHeight = max(1, (int) round($logoSecondaryWidth * $logoAspectRatio));
    }

    $success = false;
    if (extension_loaded('gd')) {
        // Increase memory limit temporarily for large images
        ini_set('memory_limit', '256M');
        $srcImg = @imagecreatefrompng($logoPath);
        if ($srcImg) {
            $srcW = imagesx($srcImg);
            $srcH = imagesy($srcImg);
            $newW = 200;
            $newH = intval(($srcH / $srcW) * $newW);
            $dstImg = imagecreatetruecolor($newW, $newH);
            imagealphablending($dstImg, false);
            imagesavealpha($dstImg, true);
            $transparent = imagecolorallocatealpha($dstImg, 0, 0, 0, 127);
            imagefilledrectangle($dstImg, 0, 0, $newW, $newH, $transparent);
            imagecopyresampled($dstImg, $srcImg, 0, 0, 0, 0, $newW, $newH, $srcW, $srcH);
            
            ob_start();
            imagepng($dstImg, null, 6);
            $pngData = ob_get_clean();
            $logoBase64 = base64_encode($pngData);
            
            imagedestroy($srcImg);
            imagedestroy($dstImg);
            $success = true;
        }
    }
    
    // Fallback if GD fails (e.g. out of memory, or not a valid PNG for GD)
    if (!$success) {
        $logoBase64 = base64_encode(file_get_contents($logoPath));
    }
}

$logoHtmlSrc = $logoBase64 ? 'cid:' . $logoContentId : '';

$filename = 'POSIS_' . ucfirst($reportType) . '_Report_' . date('Y-m-d_His') . '.xls';
$dateGenerated = date('F j, Y \a\t g:i A');
$colCount = count($reportHeaders);
$maxVal = !empty($chartValues) ? max($chartValues) : 1;
$barCols = 20;

// ── Build the HTML content ───────────────────────────────────────────────────
ob_start();
?>
<html xmlns:o="urn:schemas-microsoft-com:office:office"
      xmlns:x="urn:schemas-microsoft-com:office:excel"
      xmlns="http://www.w3.org/TR/REC-html40">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<!--[if gte mso 9]>
<xml>
 <x:ExcelWorkbook>
  <x:ExcelWorksheets>
   <x:ExcelWorksheet>
    <x:Name><?= htmlspecialchars($reportTitle) ?></x:Name>
    <x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions>
   </x:ExcelWorksheet>
   <x:ExcelWorksheet>
    <x:Name>Chart - <?= htmlspecialchars($chartTitle) ?></x:Name>
    <x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions>
   </x:ExcelWorksheet>
  </x:ExcelWorksheets>
 </x:ExcelWorkbook>
</xml>
<![endif]-->
<style>
body { font-family: Segoe UI, Arial, sans-serif; }
.data-table { border-collapse: collapse; width: 100%; }
.data-table th { background-color: #6366f1; color: #fff; font-size: 11px; font-weight: bold; text-align: center; padding: 10px 14px; border: 1px solid #4f46e5; }
.data-table td { font-size: 11px; color: #334155; padding: 8px 14px; border: 1px solid #e2e8f0; }
.row-even td { background-color: #f8fafc; }
.row-odd td { background-color: #fff; }
.num-cell { text-align: center; }
.summary-row td { background-color: #ede9fe; color: #6366f1; font-weight: bold; border-top: 2px solid #6366f1; font-size: 11px; padding: 8px 14px; }
.chart-table { border-collapse: collapse; }
.chart-table th { background-color: #6366f1; color: #fff; font-size: 11px; font-weight: bold; padding: 8px 12px; border: 1px solid #4f46e5; text-align: center; }
.chart-label { background-color: #f1f5f9; font-weight: bold; color: #334155; font-size: 11px; padding: 8px 12px; border: 1px solid #e2e8f0; }
.chart-val { text-align: center; font-size: 11px; color: #334155; padding: 8px 12px; border: 1px solid #e2e8f0; }
.bar { background-color: #6366f1; color: #fff; font-weight: bold; font-size: 10px; text-align: center; padding: 6px 2px; }
.bar-alt { background-color: #8b5cf6; color: #fff; font-weight: bold; font-size: 10px; text-align: center; padding: 6px 2px; }
.bar-empty { background-color: #f8fafc; }
.stat-row td { background-color: #ede9fe; color: #6366f1; font-weight: bold; font-size: 11px; padding: 8px 12px; border: 1px solid #c4b5fd; }
</style>
</head>
<body>

<!-- ═══════ SHEET 1 ═══════ -->
<div id="<?= htmlspecialchars($reportTitle) ?>">

<table style="width:100%; margin-bottom: 6px;">
<tr>
 <td style="vertical-align: middle; width: 160px; height: 96px; text-align: center;">
  <?php if ($logoHtmlSrc): ?>
  <img src="<?= htmlspecialchars($logoHtmlSrc) ?>" width="<?= $logoPrimaryWidth ?>" height="<?= $logoPrimaryHeight ?>" alt="Logo">
  <?php endif; ?>
 </td>
 <td style="vertical-align: middle; padding-left: 10px;">
  <span style="font-size: 22px; font-weight: bold; color: #1e293b;">POSIS Technology Group</span><br>
  <span style="font-size: 11px; color: #64748b;">Academic Employee Data Platform</span>
 </td>
</tr>
</table>

<p style="font-size: 15px; font-weight: bold; color: #6366f1; margin: 4px 0;"><?= htmlspecialchars($reportTitle) ?></p>
<p style="font-size: 10px; color: #64748b; margin: 2px 0 12px 0;">Date Generated: <?= $dateGenerated ?> &nbsp;|&nbsp; Generated By: <?= htmlspecialchars($userName) ?></p>

<table class="data-table">
<thead><tr>
<?php foreach ($reportHeaders as $h): ?>
<th><?= htmlspecialchars($h) ?></th>
<?php endforeach; ?>
</tr></thead>
<tbody>
<?php $ri = 0; foreach ($reportData as $row): $ev = ($ri % 2 === 0); $ri++; ?>
<tr class="<?= $ev ? 'row-even' : 'row-odd' ?>">
<?php foreach ($row as $k => $c): ?>
<td class="<?= (is_numeric($c) && in_array($k, ['GradeNum','Percentage','Credits','EnrollID','StudentID','CourseID','ProfID'])) ? 'num-cell' : '' ?>"><?= htmlspecialchars($c ?? 'N/A') ?></td>
<?php endforeach; ?>
</tr>
<?php endforeach; ?>
</tbody>
<tfoot><tr class="summary-row"><td colspan="<?= $colCount ?>">Total Records: <?= count($reportData) ?></td></tr></tfoot>
</table>

<br><br><br>
<table style="width:100%;">
<tr><td colspan="<?= $colCount ?>" style="font-size:12px; font-weight:bold; color:#1e293b; text-align:center;">Prepared and Verified By:</td></tr>
<tr><td style="height:50px;"></td></tr>
<tr><td colspan="<?= $colCount ?>" style="text-align:center;">
 <table style="margin: 0 auto;">
  <tr><td style="border-bottom: 1px solid #1e293b; width: 280px; height: 30px;">&nbsp;</td></tr>
  <tr><td style="text-align:center; font-size:12px; font-weight:bold; color:#1e293b;"><?= htmlspecialchars($userName) ?></td></tr>
  <tr><td style="text-align:center; font-size:10px; color:#64748b;">Signature Over Printed Name</td></tr>
  <tr><td style="text-align:center; font-size:10px; color:#64748b; padding-top:6px;">Date: ____________________</td></tr>
 </table>
</td></tr>
</table>
<br>
<p style="font-size:9px; color:#94a3b8; font-style:italic;">&copy; <?= date('Y') ?> POSIS Technology Group. System-generated report. Confidential.</p>
</div>

<br style="page-break-before: always;">

<!-- ═══════ SHEET 2 ═══════ -->
<div id="Chart - <?= htmlspecialchars($chartTitle) ?>">
<table style="width:100%; margin-bottom:8px;">
<tr>
 <td style="vertical-align:middle; width:100px; height:70px; text-align:center;">
  <?php if ($logoHtmlSrc): ?>
  <img src="<?= htmlspecialchars($logoHtmlSrc) ?>" width="<?= $logoSecondaryWidth ?>" height="<?= $logoSecondaryHeight ?>" alt="Logo">
  <?php endif; ?>
 </td>
 <td style="vertical-align:middle; padding-left:10px;">
  <span style="font-size:18px; font-weight:bold; color:#1e293b;"><?= htmlspecialchars($chartTitle) ?></span><br>
  <span style="font-size:10px; color:#64748b;">Visual representation &mdash; Generated <?= $dateGenerated ?></span>
 </td>
</tr>
</table>
<br>
<table class="chart-table">
<thead><tr>
<th style="min-width:180px;">Category</th>
<th style="min-width:80px;"><?= htmlspecialchars($chartSeriesName) ?></th>
<th style="width:10px; border:none; background:#fff;"></th>
<th colspan="<?= $barCols ?>" style="min-width:400px;">Chart Visualization</th>
</tr></thead>
<tbody>
<?php foreach ($chartLabels as $idx => $label): 
$val = $chartValues[$idx];
$bl = max(1, ($maxVal > 0) ? round(($val / $maxVal) * $barCols) : 0);
$alt = ($idx % 2 === 0);
?>
<tr>
<td class="chart-label"><?= htmlspecialchars($label) ?></td>
<td class="chart-val"><?= $val ?></td>
<td style="border:none;"></td>
<?php for ($b = 0; $b < $barCols; $b++): ?>
<?php if ($b < $bl): ?>
<td class="<?= $alt ? 'bar' : 'bar-alt' ?>"><?= ($b === $bl - 1) ? $val : '' ?></td>
<?php else: ?>
<td class="bar-empty"></td>
<?php endif; endfor; ?>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<br>
<table class="chart-table" style="width:auto;">
<tr class="stat-row"><td style="min-width:180px;">Total Categories:</td><td style="text-align:center; min-width:80px;"><?= count($chartLabels) ?></td></tr>
<?php if (!empty($chartValues)): ?>
<tr class="stat-row"><td>Maximum Value:</td><td style="text-align:center;"><?= max($chartValues) ?></td></tr>
<tr class="stat-row"><td>Minimum Value:</td><td style="text-align:center;"><?= min($chartValues) ?></td></tr>
<tr class="stat-row"><td>Average:</td><td style="text-align:center;"><?= round(array_sum($chartValues) / count($chartValues), 2) ?></td></tr>
<?php endif; ?>
</table>
<br>
<p style="font-size:9px; color:#94a3b8; font-style:italic;">&copy; <?= date('Y') ?> POSIS Technology Group. Chart data auto-generated.</p>
</div>

</body>
</html>
<?php
$htmlContent = ob_get_clean();

// ── Build MHTML output ───────────────────────────────────────────────────────
$boundary = "----=_NextPart_" . md5(time());

header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Cache-Control: max-age=0");

// MHTML envelope
echo "MIME-Version: 1.0\r\n";
echo "Content-Type: multipart/related; type=\"text/html\"; boundary=\"$boundary\"\r\n";
echo "\r\n";

// Part 1: The HTML document
echo "--$boundary\r\n";
echo "Content-Type: text/html; charset=\"UTF-8\"\r\n";
echo "Content-Transfer-Encoding: 8bit\r\n";
echo "Content-Location: file:///C:/report.htm\r\n";
echo "\r\n";
echo $htmlContent;
echo "\r\n";

// Part 2: The logo image
if ($logoBase64) {
    echo "--$boundary\r\n";
    echo "Content-Type: image/png\r\n";
    echo "Content-Transfer-Encoding: base64\r\n";
    echo "Content-ID: <$logoContentId>\r\n";
    echo "Content-Location: $logoContentLocation\r\n";
    echo "Content-Disposition: inline; filename=\"logo_thumb.png\"\r\n";
    echo "\r\n";
    // Output base64 in 76-char lines
    echo chunk_split($logoBase64, 76, "\r\n");
    echo "\r\n";
}

echo "--$boundary--\r\n";
