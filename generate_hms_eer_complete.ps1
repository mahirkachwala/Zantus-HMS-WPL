$ErrorActionPreference = 'Stop'

function Escape-Xml([string]$text) {
    if ($null -eq $text) { return '' }
    return [System.Security.SecurityElement]::Escape($text)
}

function New-CellList {
    return New-Object System.Collections.Generic.List[string]
}

function Add-Vertex {
    param(
        [System.Collections.Generic.List[string]]$Cells,
        [string]$Id,
        [string]$Value,
        [string]$Style,
        [double]$X,
        [double]$Y,
        [double]$Width,
        [double]$Height
    )

    $Cells.Add("<mxCell id='$Id' value='$(Escape-Xml $Value)' style='$Style' vertex='1' parent='1'><mxGeometry x='$X' y='$Y' width='$Width' height='$Height' as='geometry'/></mxCell>")
}

function Add-Edge {
    param(
        [System.Collections.Generic.List[string]]$Cells,
        [string]$Id,
        [string]$Source,
        [string]$Target,
        [string]$Style = "endArrow=none;html=1;rounded=0;strokeWidth=1.2",
        [string]$Value = ''
    )

    $Cells.Add("<mxCell id='$Id' value='$(Escape-Xml $Value)' edge='1' parent='1' source='$Source' target='$Target' style='$Style'><mxGeometry relative='1' as='geometry'/></mxCell>")
}

function Add-Entity {
    param(
        [System.Collections.Generic.List[string]]$Cells,
        [string]$Id,
        [string]$Label,
        [double]$X,
        [double]$Y,
        [double]$Width,
        [double]$Height,
        [string]$Kind = 'strong'
    )

    $styles = @{
        strong  = "rounded=0;whiteSpace=wrap;html=1;fillColor=#dae8fc;strokeColor=#6c8ebf;fontStyle=1"
        weak    = "rounded=0;whiteSpace=wrap;html=1;fillColor=#f8cecc;strokeColor=#b85450;strokeWidth=2;fontStyle=1"
        archive = "rounded=0;whiteSpace=wrap;html=1;fillColor=#f4cccc;strokeColor=#a61c00;strokeWidth=2;fontStyle=1"
        legacy  = "rounded=0;whiteSpace=wrap;html=1;fillColor=#e1d5e7;strokeColor=#9673a6;fontStyle=1"
        subtype = "rounded=0;whiteSpace=wrap;html=1;fillColor=#d9ead3;strokeColor=#6aa84f;fontStyle=1"
    }

    Add-Vertex -Cells $Cells -Id $Id -Value $Label -Style $styles[$Kind] -X $X -Y $Y -Width $Width -Height $Height

    if ($Kind -eq 'weak') {
        Add-Vertex -Cells $Cells -Id "${Id}_inner" -Value '' -Style "rounded=0;whiteSpace=wrap;html=1;fillColor=none;strokeColor=#b85450;strokeWidth=1" -X ($X + 5) -Y ($Y + 5) -Width ($Width - 10) -Height ($Height - 10)
    }
}

function Add-Attribute {
    param(
        [System.Collections.Generic.List[string]]$Cells,
        [string]$Id,
        [string]$Label,
        [double]$X,
        [double]$Y,
        [double]$Width,
        [double]$Height,
        [string]$Kind = 'simple'
    )

    $styles = @{
        simple    = "ellipse;whiteSpace=wrap;html=1;fillColor=#ffffff;strokeColor=#666666"
        key       = "ellipse;whiteSpace=wrap;html=1;fillColor=#ffe599;strokeColor=#b45f06;fontStyle=1"
        composite = "ellipse;whiteSpace=wrap;html=1;fillColor=#d9ead3;strokeColor=#6aa84f;fontStyle=1"
        derived   = "ellipse;whiteSpace=wrap;html=1;fillColor=#fce5cd;strokeColor=#e69138;dashed=1"
        multi     = "ellipse;whiteSpace=wrap;html=1;fillColor=#ead1dc;strokeColor=#c27ba0;double=1"
        disc      = "ellipse;whiteSpace=wrap;html=1;fillColor=#fff2cc;strokeColor=#bf9000;fontStyle=1"
    }

    Add-Vertex -Cells $Cells -Id $Id -Value $Label -Style $styles[$Kind] -X $X -Y $Y -Width $Width -Height $Height
}

function Add-Relationship {
    param(
        [System.Collections.Generic.List[string]]$Cells,
        [string]$Id,
        [string]$Label,
        [double]$X,
        [double]$Y,
        [double]$Width = 105,
        [double]$Height = 70,
        [switch]$Identifying
    )

    $style = if ($Identifying) {
        "rhombus;whiteSpace=wrap;html=1;fillColor=#ffe6cc;strokeColor=#d79b00;strokeWidth=2;fontStyle=1"
    } else {
        "rhombus;whiteSpace=wrap;html=1;fillColor=#ffe6cc;strokeColor=#d79b00;fontStyle=1"
    }

    Add-Vertex -Cells $Cells -Id $Id -Value $Label -Style $style -X $X -Y $Y -Width $Width -Height $Height

    if ($Identifying) {
        Add-Vertex -Cells $Cells -Id "${Id}_inner" -Value '' -Style "rhombus;whiteSpace=wrap;html=1;fillColor=none;strokeColor=#d79b00;strokeWidth=1" -X ($X + 6) -Y ($Y + 6) -Width ($Width - 12) -Height ($Height - 12)
    }
}

function Add-Isa {
    param(
        [System.Collections.Generic.List[string]]$Cells,
        [string]$Id,
        [double]$X,
        [double]$Y
    )

    Add-Vertex -Cells $Cells -Id $Id -Value 'ISA' -Style "shape=triangle;whiteSpace=wrap;html=1;fillColor=#fff2cc;strokeColor=#bf9000;fontStyle=1;direction=south" -X $X -Y $Y -Width 80 -Height 60
}

function Add-Note {
    param(
        [System.Collections.Generic.List[string]]$Cells,
        [string]$Id,
        [string]$Text,
        [double]$X,
        [double]$Y,
        [double]$Width,
        [double]$Height
    )

    Add-Vertex -Cells $Cells -Id $Id -Value $Text -Style "shape=note;whiteSpace=wrap;html=1;fillColor=#fff2cc;strokeColor=#d6b656" -X $X -Y $Y -Width $Width -Height $Height
}

function Parse-SqlTables {
    param([string]$Path)

    $sql = Get-Content $Path -Raw
    $tables = [ordered]@{}
    $matches = [regex]::Matches($sql, 'CREATE TABLE `(?<name>[^`]+)` \((?<body>.*?)\) ENGINE=', [System.Text.RegularExpressions.RegexOptions]::Singleline)
    foreach ($match in $matches) {
        $name = $match.Groups['name'].Value
        $columns = @()
        $lines = $match.Groups['body'].Value -split "`r?`n"
        foreach ($line in $lines) {
            $trim = $line.Trim()
            if ($trim -match '^`(?<col>[^`]+)`\s+(?<type>.+?)(,)?$') {
                $columns += [pscustomobject]@{
                    name = $Matches['col']
                    type = $Matches['type']
                }
            }
        }
        $tables[$name] = $columns
    }
    return $tables
}

function Build-PageXml {
    param(
        [string]$Id,
        [string]$Name,
        [System.Collections.Generic.List[string]]$Cells,
        [int]$PageWidth,
        [int]$PageHeight
    )

    return @"
  <diagram id='$Id' name='$Name'>
    <mxGraphModel dx='2200' dy='1400' grid='1' gridSize='10' guides='1' tooltips='1' connect='1' arrows='1' fold='1' page='1' pageScale='1' pageWidth='$PageWidth' pageHeight='$PageHeight'>
      <root>
        <mxCell id='0'/>
        <mxCell id='1' parent='0'/>
        $($Cells -join "`n        ")
      </root>
    </mxGraphModel>
  </diagram>
"@
}

$sqlPath = 'C:\Users\Mahir Kachwala\Downloads\b10_41663109_HMS (1).sql'
$tables = Parse-SqlTables -Path $sqlPath

$meta = @{
    admin = @{ label='ADMIN'; kind='strong'; pk=@('id'); ck=@('username'); fk=@{}; discriminator=@(); composite=@(); derived=@(); multi=@(); partial=@() }
    appointment = @{ label='APPOINTMENT (staging)'; kind='legacy'; pk=@('id'); ck=@(); fk=@{ doctorId='doctors.id'; userId='users.id' }; discriminator=@('appointmentType'); composite=@('appointmentDate', 'appointmentTime'); derived=@(); multi=@(); partial=@() }
    appointment_transfers = @{ label='APPOINTMENT_TRANSFER'; kind='weak'; pk=@('id'); ck=@(); fk=@{ originalAppointmentId='current_appointments.id'; transferredAppointmentId='current_appointments.id'; patientId='patients.id'; doctorId='doctors.id' }; discriminator=@(); composite=@(); derived=@(); multi=@(); partial=@('id') }
    contact_queries = @{ label='CONTACT_QUERY'; kind='strong'; pk=@('id'); ck=@(); fk=@{ user_id='users.id (nullable)'; doctor_id='doctors.id (nullable)' }; discriminator=@('portal_type'); composite=@(); derived=@(); multi=@(); partial=@() }
    contact_query_history = @{ label='CONTACT_QUERY_HISTORY'; kind='weak'; pk=@('id'); ck=@('original_query_id|portal_type'); fk=@{ original_query_id='contact_queries.id'; user_id='users.id (nullable)'; doctor_id='doctors.id (nullable)' }; discriminator=@('portal_type'); composite=@(); derived=@(); multi=@(); partial=@('id') }
    current_appointments = @{ label='APPOINTMENT (current_appointments)'; kind='strong'; pk=@('id'); ck=@(); fk=@{ doctorId='doctors.id'; userId='users.id'; patientId='patients.id' }; discriminator=@('appointmentType'); composite=@('appointmentDate', 'appointmentTime'); derived=@(); multi=@(); partial=@() }
    doctors = @{ label='DOCTOR'; kind='strong'; pk=@('id'); ck=@('docEmail'); fk=@{ specialization='doctorspecialization.id' }; discriminator=@(); composite=@(); derived=@(); multi=@(); partial=@() }
    doctorslog = @{ label='DOCTOR_LOG'; kind='weak'; pk=@('id'); ck=@(); fk=@{ uid='doctors.id' }; discriminator=@(); composite=@(); derived=@(); multi=@(); partial=@('id') }
    doctorspecialization = @{ label='DOCTOR_SPECIALIZATION'; kind='strong'; pk=@('id'); ck=@('specialization'); fk=@{}; discriminator=@(); composite=@(); derived=@(); multi=@(); partial=@() }
    feedback_entries = @{ label='FEEDBACK_ENTRY'; kind='strong'; pk=@('id'); ck=@(); fk=@{ user_id='users.id (nullable)'; doctor_id='doctors.id (nullable)' }; discriminator=@('portal_type'); composite=@(); derived=@(); multi=@(); partial=@() }
    past_appointments = @{ label='PAST_APPOINTMENT'; kind='archive'; pk=@('id'); ck=@(); fk=@{ doctorId='doctors.id'; userId='users.id'; patientId='patients.id'; originalappointmentid='current_appointments.id' }; discriminator=@('appointmentType'); composite=@('appointmentDate', 'appointmentTime'); derived=@(); multi=@(); partial=@() }
    patients = @{ label='PATIENT'; kind='strong'; pk=@('id'); ck=@(); fk=@{ userId='users.id'; doctorId='doctors.id' }; discriminator=@('patientType'); composite=@(); derived=@('patientAge'); multi=@(); partial=@() }
    payment_transactions = @{ label='PAYMENT_TRANSACTION'; kind='strong'; pk=@('id'); ck=@('transaction_ref'); fk=@{ appointment_id='current_appointments.id'; user_id='users.id' }; discriminator=@(); composite=@(); derived=@(); multi=@(); partial=@() }
    prescriptions = @{ label='PRESCRIPTION'; kind='strong'; pk=@('id'); ck=@(); fk=@{ patient_id='patients.id'; doctor_id='doctors.id'; appointment_id='current_appointments.id' }; discriminator=@(); composite=@('temperature', 'blood_pressure', 'pulse', 'weight'); derived=@(); multi=@('tests', 'medicines'); partial=@() }
    tblcontactus = @{ label='TBLCONTACTUS'; kind='legacy'; pk=@('id'); ck=@(); fk=@{}; discriminator=@(); composite=@(); derived=@(); multi=@(); partial=@() }
    tblmedicalhistory = @{ label='MEDICAL_HISTORY'; kind='weak'; pk=@('ID'); ck=@(); fk=@{ PatientID='tblpatient.ID' }; discriminator=@(); composite=@(); derived=@(); multi=@(); partial=@('ID') }
    tblpatient = @{ label='TBLPATIENT'; kind='legacy'; pk=@('ID'); ck=@(); fk=@{ Docid='doctors.id' }; discriminator=@(); composite=@(); derived=@(); multi=@(); partial=@() }
    userlog = @{ label='USER_LOG'; kind='weak'; pk=@('id'); ck=@(); fk=@{ uid='users.id' }; discriminator=@(); composite=@(); derived=@(); multi=@(); partial=@('id') }
    users = @{ label='USER'; kind='strong'; pk=@('id'); ck=@('email'); fk=@{}; discriminator=@(); composite=@('address', 'city'); derived=@(); multi=@(); partial=@() }
}

# Page 1: conceptual EER
$p1 = New-CellList
Add-Vertex -Cells $p1 -Id 'p1_title' -Value 'Hospital Management System - Conceptual EER (19 Tables, 24 Relationships, 3 Generalizations)' -Style "text;html=1;strokeColor=none;fillColor=none;align=center;fontSize=22;fontStyle=1" -X 650 -Y 20 -Width 1800 -Height 30

Add-Entity -Cells $p1 -Id 'p1_ds' -Label 'DOCTOR_SPECIALIZATION' -X 50 -Y 80 -Width 230 -Height 60
Add-Entity -Cells $p1 -Id 'p1_doctor' -Label 'DOCTOR' -X 430 -Y 80 -Width 170 -Height 60
Add-Entity -Cells $p1 -Id 'p1_doctorlog' -Label 'DOCTOR_LOG' -X 670 -Y 70 -Width 170 -Height 70 -Kind 'weak'
Add-Entity -Cells $p1 -Id 'p1_tblpatient' -Label 'TBLPATIENT (legacy)' -X 930 -Y 80 -Width 190 -Height 60 -Kind 'legacy'
Add-Entity -Cells $p1 -Id 'p1_medhist' -Label 'MEDICAL_HISTORY' -X 1180 -Y 70 -Width 190 -Height 70 -Kind 'weak'
Add-Entity -Cells $p1 -Id 'p1_admin' -Label 'ADMIN' -X 1490 -Y 80 -Width 120 -Height 60
Add-Entity -Cells $p1 -Id 'p1_tblcontactus' -Label 'TBLCONTACTUS (legacy)' -X 1670 -Y 80 -Width 220 -Height 60 -Kind 'legacy'

Add-Entity -Cells $p1 -Id 'p1_user' -Label 'USER' -X 60 -Y 420 -Width 150 -Height 60
Add-Entity -Cells $p1 -Id 'p1_userlog' -Label 'USER_LOG' -X 260 -Y 410 -Width 160 -Height 70 -Kind 'weak'
Add-Entity -Cells $p1 -Id 'p1_patient' -Label 'PATIENT' -X 520 -Y 410 -Width 180 -Height 70
Add-Entity -Cells $p1 -Id 'p1_consult' -Label 'CONSULTANCY_PATIENT' -X 420 -Y 650 -Width 200 -Height 60 -Kind 'subtype'
Add-Entity -Cells $p1 -Id 'p1_admitted' -Label 'ADMITTED_PATIENT' -X 660 -Y 650 -Width 200 -Height 60 -Kind 'subtype'
Add-Isa -Cells $p1 -Id 'p1_isa_patient' -X 575 -Y 540
Add-Attribute -Cells $p1 -Id 'p1_disc_patient' -Label 'patientType (d, partial)' -X 520 -Y 500 -Width 180 -Height 30 -Kind 'disc'

Add-Entity -Cells $p1 -Id 'p1_appt_stage' -Label 'APPOINTMENT (staging)' -X 60 -Y 960 -Width 220 -Height 60 -Kind 'legacy'
Add-Entity -Cells $p1 -Id 'p1_appt' -Label 'APPOINTMENT (current_appointments)' -X 470 -Y 960 -Width 320 -Height 70
Add-Entity -Cells $p1 -Id 'p1_online' -Label 'ONLINE_APPOINTMENT' -X 420 -Y 1220 -Width 190 -Height 60 -Kind 'subtype'
Add-Entity -Cells $p1 -Id 'p1_walkin' -Label 'WALK_IN_APPOINTMENT' -X 660 -Y 1220 -Width 190 -Height 60 -Kind 'subtype'
Add-Isa -Cells $p1 -Id 'p1_isa_appt' -X 590 -Y 1110
Add-Attribute -Cells $p1 -Id 'p1_disc_appt' -Label 'appointmentType (d, partial)' -X 500 -Y 1070 -Width 200 -Height 30 -Kind 'disc'

Add-Entity -Cells $p1 -Id 'p1_past' -Label 'PAST_APPOINTMENT' -X 890 -Y 960 -Width 220 -Height 70 -Kind 'archive'
Add-Entity -Cells $p1 -Id 'p1_payment' -Label 'PAYMENT_TRANSACTION' -X 1230 -Y 960 -Width 240 -Height 70
Add-Entity -Cells $p1 -Id 'p1_pres' -Label 'PRESCRIPTION' -X 500 -Y 1500 -Width 210 -Height 70
Add-Entity -Cells $p1 -Id 'p1_transfer' -Label 'APPOINTMENT_TRANSFER' -X 930 -Y 1500 -Width 260 -Height 70 -Kind 'weak'

Add-Entity -Cells $p1 -Id 'p1_cq' -Label 'CONTACT_QUERY' -X 1690 -Y 420 -Width 210 -Height 70
Add-Entity -Cells $p1 -Id 'p1_cqh' -Label 'CONTACT_QUERY_HISTORY' -X 1980 -Y 420 -Width 250 -Height 70 -Kind 'weak'
Add-Entity -Cells $p1 -Id 'p1_uq' -Label 'USER_QUERY' -X 1620 -Y 670 -Width 160 -Height 60 -Kind 'subtype'
Add-Entity -Cells $p1 -Id 'p1_dq' -Label 'DOCTOR_QUERY' -X 1840 -Y 670 -Width 180 -Height 60 -Kind 'subtype'
Add-Isa -Cells $p1 -Id 'p1_isa_cq' -X 1760 -Y 560
Add-Attribute -Cells $p1 -Id 'p1_disc_cq' -Label 'portal_type (d, total)' -X 1700 -Y 520 -Width 200 -Height 30 -Kind 'disc'

Add-Entity -Cells $p1 -Id 'p1_feedback' -Label 'FEEDBACK_ENTRY' -X 1830 -Y 960 -Width 210 -Height 70

# EER-specific attributes
Add-Attribute -Cells $p1 -Id 'p1_address' -Label 'ADDRESS' -X 30 -Y 540 -Width 90 -Height 30 -Kind 'composite'
Add-Attribute -Cells $p1 -Id 'p1_address_a' -Label 'address' -X 0 -Y 600 -Width 80 -Height 28
Add-Attribute -Cells $p1 -Id 'p1_address_c' -Label 'city' -X 110 -Y 600 -Width 60 -Height 28
Add-Edge -Cells $p1 -Id 'p1_e_user_address' -Source 'p1_user' -Target 'p1_address'
Add-Edge -Cells $p1 -Id 'p1_e_address_a' -Source 'p1_address' -Target 'p1_address_a'
Add-Edge -Cells $p1 -Id 'p1_e_address_c' -Source 'p1_address' -Target 'p1_address_c'

Add-Attribute -Cells $p1 -Id 'p1_schedule' -Label 'SCHEDULE' -X 410 -Y 820 -Width 95 -Height 30 -Kind 'composite'
Add-Attribute -Cells $p1 -Id 'p1_appt_date' -Label 'appointmentDate' -X 350 -Y 870 -Width 120 -Height 28
Add-Attribute -Cells $p1 -Id 'p1_appt_time' -Label 'appointmentTime' -X 500 -Y 870 -Width 120 -Height 28
Add-Edge -Cells $p1 -Id 'p1_e_appt_schedule' -Source 'p1_appt' -Target 'p1_schedule'
Add-Edge -Cells $p1 -Id 'p1_e_schedule_date' -Source 'p1_schedule' -Target 'p1_appt_date'
Add-Edge -Cells $p1 -Id 'p1_e_schedule_time' -Source 'p1_schedule' -Target 'p1_appt_time'

Add-Attribute -Cells $p1 -Id 'p1_vitals' -Label 'VITALS' -X 560 -Y 1370 -Width 80 -Height 30 -Kind 'composite'
Add-Attribute -Cells $p1 -Id 'p1_temp' -Label 'temperature' -X 430 -Y 1330 -Width 100 -Height 28
Add-Attribute -Cells $p1 -Id 'p1_bp' -Label 'blood_pressure' -X 640 -Y 1330 -Width 120 -Height 28
Add-Attribute -Cells $p1 -Id 'p1_pulse' -Label 'pulse' -X 540 -Y 1610 -Width 60 -Height 28
Add-Attribute -Cells $p1 -Id 'p1_tests' -Label 'tests' -X 720 -Y 1505 -Width 60 -Height 28 -Kind 'multi'
Add-Attribute -Cells $p1 -Id 'p1_meds' -Label 'medicines' -X 430 -Y 1600 -Width 85 -Height 28 -Kind 'multi'
Add-Edge -Cells $p1 -Id 'p1_e_pres_vitals' -Source 'p1_pres' -Target 'p1_vitals'
Add-Edge -Cells $p1 -Id 'p1_e_vital_temp' -Source 'p1_vitals' -Target 'p1_temp'
Add-Edge -Cells $p1 -Id 'p1_e_vital_bp' -Source 'p1_vitals' -Target 'p1_bp'
Add-Edge -Cells $p1 -Id 'p1_e_vital_pulse' -Source 'p1_vitals' -Target 'p1_pulse'
Add-Edge -Cells $p1 -Id 'p1_e_pres_tests' -Source 'p1_pres' -Target 'p1_tests'
Add-Edge -Cells $p1 -Id 'p1_e_pres_meds' -Source 'p1_pres' -Target 'p1_meds'

Add-Attribute -Cells $p1 -Id 'p1_patient_age' -Label 'patientAge (derived)' -X 735 -Y 450 -Width 120 -Height 28 -Kind 'derived'
Add-Attribute -Cells $p1 -Id 'p1_adm_date' -Label 'admissionDate' -X 680 -Y 740 -Width 105 -Height 28
Add-Attribute -Cells $p1 -Id 'p1_emergency' -Label 'isEmergency' -X 570 -Y 740 -Width 90 -Height 28
Add-Edge -Cells $p1 -Id 'p1_e_patient_age' -Source 'p1_patient' -Target 'p1_patient_age'
Add-Edge -Cells $p1 -Id 'p1_e_admitted_date' -Source 'p1_admitted' -Target 'p1_adm_date'
Add-Edge -Cells $p1 -Id 'p1_e_admitted_emg' -Source 'p1_admitted' -Target 'p1_emergency'

# ISA links
Add-Edge -Cells $p1 -Id 'p1_isa_patient_super' -Source 'p1_patient' -Target 'p1_isa_patient' -Style "endArrow=none;html=1;rounded=0;strokeWidth=1.4"
Add-Edge -Cells $p1 -Id 'p1_isa_patient_cons' -Source 'p1_isa_patient' -Target 'p1_consult' -Style "endArrow=none;html=1;rounded=0;strokeWidth=1.4"
Add-Edge -Cells $p1 -Id 'p1_isa_patient_adm' -Source 'p1_isa_patient' -Target 'p1_admitted' -Style "endArrow=none;html=1;rounded=0;strokeWidth=1.4"
Add-Edge -Cells $p1 -Id 'p1_isa_appt_super' -Source 'p1_appt' -Target 'p1_isa_appt' -Style "endArrow=none;html=1;rounded=0;strokeWidth=1.4"
Add-Edge -Cells $p1 -Id 'p1_isa_appt_on' -Source 'p1_isa_appt' -Target 'p1_online' -Style "endArrow=none;html=1;rounded=0;strokeWidth=1.4"
Add-Edge -Cells $p1 -Id 'p1_isa_appt_walk' -Source 'p1_isa_appt' -Target 'p1_walkin' -Style "endArrow=none;html=1;rounded=0;strokeWidth=1.4"
Add-Edge -Cells $p1 -Id 'p1_isa_cq_super' -Source 'p1_cq' -Target 'p1_isa_cq' -Style "endArrow=none;html=1;rounded=0;strokeWidth=1.4"
Add-Edge -Cells $p1 -Id 'p1_isa_cq_uq' -Source 'p1_isa_cq' -Target 'p1_uq' -Style "endArrow=none;html=1;rounded=0;strokeWidth=1.4"
Add-Edge -Cells $p1 -Id 'p1_isa_cq_dq' -Source 'p1_isa_cq' -Target 'p1_dq' -Style "endArrow=none;html=1;rounded=0;strokeWidth=1.4"

# Relationships R1-R24
$relationships = @(
    @{ id='p1_r1';  label="has`n1:M";        x=300;  y=75;  a='p1_ds';      b='p1_doctor'; identifying=$false },
    @{ id='p1_r2';  label="registers as`n1:M"; x=300; y=410; a='p1_user';    b='p1_patient'; identifying=$false },
    @{ id='p1_r3';  label="treats`n1:M";     x=515;  y=270; a='p1_doctor';   b='p1_patient'; identifying=$false },
    @{ id='p1_r4';  label="books`n1:M";      x=250;  y=790; a='p1_user';     b='p1_appt'; identifying=$false },
    @{ id='p1_r5';  label="attends`n1:M";    x=420;  y=790; a='p1_doctor';   b='p1_appt'; identifying=$false },
    @{ id='p1_r6';  label="has`n1:M";        x=820;  y=790; a='p1_patient';  b='p1_appt'; identifying=$false },
    @{ id='p1_r7';  label="generates`n1:1";  x=550;  y=1380; a='p1_appt';    b='p1_pres'; identifying=$false },
    @{ id='p1_r8';  label="writes`n1:M";     x=330;  y=1360; a='p1_doctor';  b='p1_pres'; identifying=$false },
    @{ id='p1_r9';  label="receives`n1:M";   x=760;  y=1360; a='p1_patient'; b='p1_pres'; identifying=$false },
    @{ id='p1_r10'; label="has`n1:M";        x=1120; y=790; a='p1_appt';     b='p1_payment'; identifying=$false },
    @{ id='p1_r11'; label="makes`n1:M";      x=1310; y=610; a='p1_user';     b='p1_payment'; identifying=$false },
    @{ id='p1_r12'; label="identifies`n1:M"; x=980;  y=1160; a='p1_appt';    b='p1_transfer'; identifying=$true },
    @{ id='p1_r13'; label="involved_in`n1:M";x=820;  y=1360; a='p1_doctor';  b='p1_transfer'; identifying=$false },
    @{ id='p1_r14'; label="subject_of`n1:M"; x=1160; y=1360; a='p1_patient'; b='p1_transfer'; identifying=$false },
    @{ id='p1_r15'; label="submits`n1:M";    x=1540; y=410; a='p1_user';     b='p1_cq'; identifying=$false },
    @{ id='p1_r16'; label="submits`n1:M";    x=1470; y=250; a='p1_doctor';   b='p1_cq'; identifying=$false },
    @{ id='p1_r17'; label="identifies`n1:M"; x=1920; y=530; a='p1_cq';       b='p1_cqh'; identifying=$true },
    @{ id='p1_r18'; label="submits`n1:M";    x=1590; y=860; a='p1_user';     b='p1_feedback'; identifying=$false },
    @{ id='p1_r19'; label="submits`n1:M";    x=1600; y=700; a='p1_doctor';   b='p1_feedback'; identifying=$false },
    @{ id='p1_r20'; label="identifies`n1:M"; x=170;  y=290; a='p1_user';     b='p1_userlog'; identifying=$true },
    @{ id='p1_r21'; label="identifies`n1:M"; x=650;  y=170; a='p1_doctor';   b='p1_doctorlog'; identifying=$true },
    @{ id='p1_r22'; label="treats`n1:M";     x=770;  y=250; a='p1_doctor';   b='p1_tblpatient'; identifying=$false },
    @{ id='p1_r23'; label="identifies`n1:M"; x=1125; y=250; a='p1_tblpatient'; b='p1_medhist'; identifying=$true },
    @{ id='p1_r24'; label="archived_as`n1:1";x=820;  y=960; a='p1_appt';     b='p1_past'; identifying=$false }
)

foreach ($r in $relationships) {
    Add-Relationship -Cells $p1 -Id $r.id -Label $r.label -X $r.x -Y $r.y -Identifying:([bool]$r.identifying)
    Add-Edge -Cells $p1 -Id "$($r.id)_a" -Source $r.a -Target $r.id
    Add-Edge -Cells $p1 -Id "$($r.id)_b" -Source $r.b -Target $r.id
}

# Workflow-only staging note
Add-Relationship -Cells $p1 -Id 'p1_r_stage' -Label "staged_into`nworkflow" -X 310 -Y 960 -Width 115 -Height 70
Add-Edge -Cells $p1 -Id 'p1_r_stage_a' -Source 'p1_appt_stage' -Target 'p1_r_stage' -Style "endArrow=none;html=1;rounded=0;strokeWidth=1.1;dashed=1"
Add-Edge -Cells $p1 -Id 'p1_r_stage_b' -Source 'p1_appt' -Target 'p1_r_stage' -Style "endArrow=none;html=1;rounded=0;strokeWidth=1.1;dashed=1"

Add-Note -Cells $p1 -Id 'p1_legend' -Text "Legend`nBlue = strong entity`nRed double = weak entity`nRed solid = archive entity`nPurple = legacy physical table`nGreen = subtype`nOrange double diamond = identifying relationship`n0 M:N relationships in this schema" -X 2130 -Y 40 -Width 240 -Height 200
Add-Note -Cells $p1 -Id 'p1_participation' -Text "Participation summary from md`n- Child/weak/archive entities are total participants`n- USER/DOCTOR/SPECIALIZATION sides are usually partial`n- CONTACT_QUERY and FEEDBACK_ENTRY are partial because nullable role FKs are allowed`n- APPOINTMENT and PATIENT specializations are disjoint + partial`n- CONTACT_QUERY specialization is disjoint + total" -X 2130 -Y 270 -Width 260 -Height 220
Add-Note -Cells $p1 -Id 'p1_standalone' -Text "Standalone physical tables`n- ADMIN has no strong FK participation in the conceptual model`n- TBLCONTACTUS is a legacy public form table kept separate from CONTACT_QUERY" -X 2130 -Y 520 -Width 260 -Height 130
Add-Note -Cells $p1 -Id 'p1_page2' -Text "Page 2 contains the full SQL schema listing for all 19 tables, including every column and inferred PK/CK/FK tags from the real dump." -X 2130 -Y 690 -Width 260 -Height 110

# Page 2: full physical schema
$p2 = New-CellList
Add-Vertex -Cells $p2 -Id 'p2_title' -Value 'Hospital Management System - Full Physical Schema from SQL Dump' -Style "text;html=1;strokeColor=none;fillColor=none;align=center;fontSize=22;fontStyle=1" -X 850 -Y 20 -Width 1600 -Height 30
Add-Note -Cells $p2 -Id 'p2_note' -Text "This page is generated from the actual SQL dump. The dump defines indexes and unique keys but does not declare physical FOREIGN KEY constraints; FK tags below are inferred from column names + the reviewed EER markdown." -X 2550 -Y 40 -Width 360 -Height 120

$styleMap = @{
    strong  = "rounded=0;whiteSpace=wrap;html=1;fillColor=#dae8fc;strokeColor=#6c8ebf;align=left;verticalAlign=top;spacing=6"
    weak    = "rounded=0;whiteSpace=wrap;html=1;fillColor=#f8cecc;strokeColor=#b85450;strokeWidth=2;align=left;verticalAlign=top;spacing=6"
    archive = "rounded=0;whiteSpace=wrap;html=1;fillColor=#f4cccc;strokeColor=#a61c00;strokeWidth=2;align=left;verticalAlign=top;spacing=6"
    legacy  = "rounded=0;whiteSpace=wrap;html=1;fillColor=#e1d5e7;strokeColor=#9673a6;align=left;verticalAlign=top;spacing=6"
}

function Format-EntityHtml {
    param(
        [string]$TableName,
        [hashtable]$Info,
        [object[]]$Columns
    )

    $lines = New-Object System.Collections.Generic.List[string]
    $lines.Add("<b>$([System.Security.SecurityElement]::Escape($Info.label))</b>")
    $lines.Add("<font style='font-size:9px;color:#666'>Physical table: $([System.Security.SecurityElement]::Escape($TableName))</font>")

    foreach ($col in $Columns) {
        $tags = New-Object System.Collections.Generic.List[string]
        if ($Info.partial -contains $col.name) {
            $tags.Add('Partial Key')
        } elseif ($Info.pk -contains $col.name) {
            $tags.Add('PK')
        }
        if ($Info.ck -contains $col.name) {
            $tags.Add('CK')
        }
        if ($Info.discriminator -contains $col.name) {
            $tags.Add('DISC')
        }
        if ($Info.composite -contains $col.name) {
            $tags.Add('C')
        }
        if ($Info.derived -contains $col.name) {
            $tags.Add('D')
        }
        if ($Info.multi -contains $col.name) {
            $tags.Add('MV')
        }
        if ($Info.fk.ContainsKey($col.name)) {
            $tags.Add("FK->$($Info.fk[$col.name])")
        }

        $prefix = if ($tags.Count -gt 0) { '[' + ($tags -join ', ') + '] ' } else { '' }
        $lines.Add("$prefix$($col.name) : $([System.Security.SecurityElement]::Escape($col.type))")
    }

    switch ($TableName) {
        'patients' {
            $lines.Add("<font style='font-size:9px;color:#2b78e4'>ISA: patientType -> Consultancy_Patient | Admitted_Patient</font>")
        }
        'current_appointments' {
            $lines.Add("<font style='font-size:9px;color:#2b78e4'>ISA: appointmentType -> Online_Appointment | Walk_In_Appointment</font>")
        }
        'contact_queries' {
            $lines.Add("<font style='font-size:9px;color:#2b78e4'>ISA: portal_type -> User_Query | Doctor_Query</font>")
        }
        'appointment' {
            $lines.Add("<font style='font-size:9px;color:#8a6d3b'>Workflow note: staging table copied into current_appointments</font>")
        }
        'prescriptions' {
            $lines.Add("<font style='font-size:9px;color:#8e7cc3'>MV stored as text: tests, medicines</font>")
        }
        'past_appointments' {
            $lines.Add("<font style='font-size:9px;color:#cc0000'>Archive note: originalappointmentid traces the source current appointment</font>")
        }
    }

    return ($lines -join '<br/>')
}

$rows = @(
    @('admin', 'users', 'doctorspecialization', 'doctors'),
    @('patients', 'current_appointments', 'prescriptions', 'payment_transactions'),
    @('contact_queries', 'feedback_entries', 'past_appointments', 'appointment'),
    @('userlog', 'doctorslog', 'appointment_transfers', 'contact_query_history'),
    @('tblpatient', 'tblmedicalhistory', 'tblcontactus')
)

$xPositions = @(40, 830, 1620, 2410)
$y = 180
$boxWidth = 740
$rowGap = 40

foreach ($row in $rows) {
    $heights = @()
    foreach ($tableName in $row) {
        $info = $meta[$tableName]
        $cols = $tables[$tableName]
        $height = [Math]::Max(140, 58 + ($cols.Count * 16))
        if ($tableName -in @('patients', 'current_appointments', 'contact_queries', 'appointment', 'prescriptions', 'past_appointments')) {
            $height += 20
        }
        $heights += $height
    }

    $rowHeight = ($heights | Measure-Object -Maximum).Maximum
    for ($i = 0; $i -lt $row.Count; $i++) {
        $tableName = $row[$i]
        $info = $meta[$tableName]
        $html = Format-EntityHtml -TableName $tableName -Info $info -Columns $tables[$tableName]
        Add-Vertex -Cells $p2 -Id "p2_$tableName" -Value $html -Style $styleMap[$info.kind] -X $xPositions[$i] -Y $y -Width $boxWidth -Height $heights[$i]
    }
    $y += $rowHeight + $rowGap
}

Add-Note -Cells $p2 -Id 'p2_summary' -Text "Schema completeness summary`n- 19 physical tables from the SQL dump`n- 11 strong entities, 5 weak entities, 1 archive entity, 1 legacy standalone family`n- 24 conceptual relationships from the reviewed EER markdown`n- 3 ISA hierarchies implemented conceptually on Page 1`n- No declared FOREIGN KEY constraints in the dump; logical FKs inferred from ids and indexes" -X 2550 -Y 190 -Width 360 -Height 180
Add-Note -Cells $p2 -Id 'p2_uniques' -Text "Actual unique keys in SQL dump`n- admin.username`n- users.email`n- doctors.docEmail`n- doctorspecialization.specialization`n- payment_transactions.transaction_ref`n- contact_query_history.(original_query_id, portal_type)" -X 2550 -Y 400 -Width 360 -Height 150
Add-Note -Cells $p2 -Id 'p2_caution' -Text "Conceptual vs physical`n- doctor.contactno is not UNIQUE in this SQL dump, even though it may be treated as a candidate key in the reviewed markdown`n- appointment is a physical staging table, while current_appointments is the canonical appointment entity in the EER explanation" -X 2550 -Y 580 -Width 360 -Height 160

$page1Xml = Build-PageXml -Id 'hms-conceptual' -Name 'Conceptual EER' -Cells $p1 -PageWidth 2800 -PageHeight 1900
$page2Xml = Build-PageXml -Id 'hms-physical' -Name 'Full SQL Schema' -Cells $p2 -PageWidth 3000 -PageHeight 3300

$xml = @"
<mxfile host='app.diagrams.net' version='24.7.17'>
$page1Xml
$page2Xml
</mxfile>
"@

$primaryOut = Join-Path $PSScriptRoot 'hms_eer_complete.drawio'
$compatOut = Join-Path $PSScriptRoot 'hms_eer_chen.drawio'
Set-Content -Path $primaryOut -Value $xml -Encoding UTF8
Set-Content -Path $compatOut -Value $xml -Encoding UTF8
Write-Host "Generated $primaryOut"
Write-Host "Generated $compatOut"
