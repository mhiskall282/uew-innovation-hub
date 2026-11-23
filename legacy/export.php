 <?php
/* Exports projects to Excel (.xlsx). Same year filter as the dashboard:
   export.php?year=all | nodate | 2025 ...   (add &format=csv for CSV) */

require_once __DIR__ . '/auth.php';
$conn = db();

/* ---------- Year filter ---------- */
$year = $_GET['year'] ?? 'all';
$where = '1=1';
$types = '';
$params = [];

if ($year === 'nodate') {
    $where = 'i.`Start Date` IS NULL';
} elseif (ctype_digit($year)) {
    $where = 'YEAR(i.`Start Date`) = ?';
    $types = 'i';
    $params = [(int)$year];
} else {
    $year = 'all';
}

/* ---------- Data ---------- */
$sql = "SELECT i.`project ID` AS id,
               i.`Project Title` AS title,
               i.`Number of Beneficiaries` AS beneficiaries,
               i.`Start Date` AS start_date,
               i.`End Date` AS end_date,
               i.`Project Report` AS report,
               GROUP_CONCAT(CONCAT(o.organization_name, ' (', po.role, ')')
                            ORDER BY o.organization_name SEPARATOR '; ') AS orgs
        FROM innovation_hub i
        LEFT JOIN project_organisations po ON i.`project ID` = po.project_id
        LEFT JOIN organization o ON po.organization_id = o.organization_id
        WHERE $where
        GROUP BY i.`project ID`, i.`Project Title`, i.`Number of Beneficiaries`,
                 i.`Start Date`, i.`End Date`, i.`Project Report`
        ORDER BY i.`Start Date` DESC, i.`project ID` ASC";

$stmt = $conn->prepare($sql);
if ($types !== '') {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

function fmt_date($d) {
    return $d ? date('d M Y', strtotime($d)) : '';
}

$header = ['Project ID', 'Project Title', 'Beneficiaries', 'Start Date', 'End Date', 'Organisations & Roles', 'Report Uploaded'];
$data = [];
$totalBeneficiaries = 0;

foreach ($result as $r) {
    $totalBeneficiaries += (int)$r['beneficiaries'];
    $data[] = [
        (int)$r['id'],
        (string)$r['title'],
        (int)$r['beneficiaries'],
        fmt_date($r['start_date']),
        fmt_date($r['end_date']),
        (string)$r['orgs'],
        $r['report'] ? 'Yes' : 'No',
    ];
}

$data[] = ['', 'TOTAL (' . count($result) . ' projects)', $totalBeneficiaries, '', '', '', ''];

$label = $year === 'all' ? 'all-years' : $year;
$filename = 'innovation_hub_projects_' . $label . '_' . date('Y-m-d');

/* ---------- Excel (.xlsx) ---------- */
function xml_text($v) {
    $v = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', (string)$v);
    return htmlspecialchars($v, ENT_XML1 | ENT_QUOTES, 'UTF-8');
}

function build_xlsx($header, $data, $path) {

    $letters = ['A', 'B', 'C', 'D', 'E', 'F', 'G'];
    $widths  = [11, 55, 14, 14, 14, 60, 16];

    $cols = '';
    foreach ($widths as $i => $w) {
        $n = $i + 1;
        $cols .= '<col min="' . $n . '" max="' . $n . '" width="' . $w . '" customWidth="1"/>';
    }

    $rowsXml = '<row r="1">';
    foreach ($header as $i => $h) {
        $rowsXml .= '<c r="' . $letters[$i] . '1" t="inlineStr" s="1"><is><t>' . xml_text($h) . '</t></is></c>';
    }
    $rowsXml .= '</row>';

    $lastIndex = count($data) - 1;

    foreach ($data as $ri => $row) {
        $r = $ri + 2;
        $isTotal = ($ri === $lastIndex);
        $rowsXml .= '<row r="' . $r . '">';
        foreach ($row as $ci => $val) {
            $ref = $letters[$ci] . $r;
            $style = ($isTotal) ? 1 : (($ci === 1 || $ci === 5) ? 2 : 0);
            if (is_int($val)) {
                $rowsXml .= '<c r="' . $ref . '" s="' . $style . '"><v>' . $val . '</v></c>';
            } elseif ($val === '') {
                $rowsXml .= '<c r="' . $ref . '" s="' . $style . '"/>';
            } else {
                $rowsXml .= '<c r="' . $ref . '" t="inlineStr" s="' . $style . '"><is><t xml:space="preserve">' . xml_text($val) . '</t></is></c>';
            }
        }
        $rowsXml .= '</row>';
    }

    $sheet = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
        . '<sheetViews><sheetView workbookViewId="0"><pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>'
        . '<cols>' . $cols . '</cols>'
        . '<sheetData>' . $rowsXml . '</sheetData>'
        . '</worksheet>';

    $styles = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
        . '<fonts count="2"><font><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="11"/><name val="Calibri"/></font></fonts>'
        . '<fills count="3"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill>'
        . '<fill><patternFill patternType="solid"><fgColor rgb="FFE0E7FF"/><bgColor indexed="64"/></patternFill></fill></fills>'
        . '<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
        . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
        . '<cellXfs count="3">'
        . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
        . '<xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1"/>'
        . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0" applyAlignment="1"><alignment vertical="top" wrapText="1"/></xf>'
        . '</cellXfs></styleSheet>';

    $zip = new ZipArchive();
    $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);

    $zip->addFromString('[Content_Types].xml',
        '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
        . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
        . '<Default Extension="xml" ContentType="application/xml"/>'
        . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
        . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
        . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
        . '</Types>');

    $zip->addFromString('_rels/.rels',
        '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
        . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
        . '</Relationships>');

    $zip->addFromString('xl/workbook.xml',
        '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
        . '<sheets><sheet name="Projects" sheetId="1" r:id="rId1"/></sheets></workbook>');

    $zip->addFromString('xl/_rels/workbook.xml.rels',
        '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
        . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
        . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
        . '</Relationships>');

    $zip->addFromString('xl/styles.xml', $styles);
    $zip->addFromString('xl/worksheets/sheet1.xml', $sheet);
    $zip->close();
}

$wantCsv = (($_GET['format'] ?? '') === 'csv') || !class_exists('ZipArchive');

if (!$wantCsv) {

    $tmp = tempnam(sys_get_temp_dir(), 'xlsx');
    build_xlsx($header, $data, $tmp);

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '.xlsx"');
    header('Content-Length: ' . filesize($tmp));
    header('Cache-Control: private, max-age=0, must-revalidate');
    readfile($tmp);
    unlink($tmp);
    exit;
}

/* ---------- CSV fallback (opens in Excel) ---------- */
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
echo "\xEF\xBB\xBF";
$out = fopen('php://output', 'w');
fputcsv($out, $header);
foreach ($data as $row) {
    fputcsv($out, $row);
}
fclose($out);
exit;