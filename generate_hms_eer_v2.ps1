$ErrorActionPreference = 'Stop'

function Escape-Xml([string]$text) {
    if ($null -eq $text) { return '' }
    return [System.Security.SecurityElement]::Escape($text)
}

$cells = New-Object System.Collections.Generic.List[string]

function Add-Cell {
    param(
        [string]$Id,
        [string]$Value,
        [string]$Style,
        [double]$X,
        [double]$Y,
        [double]$Width,
        [double]$Height,
        [string]$Parent = '1',
        [switch]$Vertex
    )

    $escaped = Escape-Xml $Value
    $v = if ($Vertex) { " vertex='1'" } else { '' }
    $cells.Add("<mxCell id='$Id' value='$escaped' style='$Style'$v parent='$Parent'><mxGeometry x='$X' y='$Y' width='$Width' height='$Height' as='geometry'/></mxCell>")
}

function Add-Edge {
    param(
        [string]$Id,
        [string]$Source,
        [string]$Target,
        [string]$Style,
        [string]$Value = ''
    )

    $escaped = Escape-Xml $Value
    $cells.Add("<mxCell id='$Id' value='$escaped' edge='1' parent='1' source='$Source' target='$Target' style='$Style'><mxGeometry relative='1' as='geometry'/></mxCell>")
}

function Add-Label {
    param(
        [string]$Id,
        [string]$Value,
        [double]$X,
        [double]$Y,
        [double]$Width = 24,
        [double]$Height = 18
    )

    Add-Cell -Id $Id -Value $Value -Style "text;html=1;strokeColor=none;fillColor=none;align=center;verticalAlign=middle;fontSize=12;fontStyle=1" -X $X -Y $Y -Width $Width -Height $Height -Vertex
}

function Add-Entity {
    param(
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

    Add-Cell -Id $Id -Value $Label -Style $styles[$Kind] -X $X -Y $Y -Width $Width -Height $Height -Vertex

    if ($Kind -eq 'weak') {
        Add-Cell -Id "${Id}_inner" -Value '' -Style "rounded=0;whiteSpace=wrap;html=1;fillColor=none;strokeColor=#b85450;strokeWidth=1" -X ($X + 5) -Y ($Y + 5) -Width ($Width - 10) -Height ($Height - 10) -Vertex
    }
}

function Add-Attribute {
    param(
        [string]$Id,
        [string]$Label,
        [double]$X,
        [double]$Y,
        [double]$Width,
        [double]$Height,
        [string]$Kind = 'simple'
    )

    $styles = @{
        simple     = "ellipse;whiteSpace=wrap;html=1;fillColor=#ffffff;strokeColor=#666666"
        key        = "ellipse;whiteSpace=wrap;html=1;fillColor=#ffe599;strokeColor=#b45f06;fontStyle=1"
        candidate  = "ellipse;whiteSpace=wrap;html=1;fillColor=#fff2cc;strokeColor=#bf9000;fontStyle=1"
        composite  = "ellipse;whiteSpace=wrap;html=1;fillColor=#d9ead3;strokeColor=#6aa84f;fontStyle=1"
        derived    = "ellipse;whiteSpace=wrap;html=1;fillColor=#fce5cd;strokeColor=#e69138;dashed=1"
        multi      = "ellipse;whiteSpace=wrap;html=1;fillColor=#ead1dc;strokeColor=#c27ba0;double=1"
        partialkey = "ellipse;whiteSpace=wrap;html=1;fillColor=#f4cccc;strokeColor=#cc0000;fontStyle=1"
    }

    Add-Cell -Id $Id -Value $Label -Style $styles[$Kind] -X $X -Y $Y -Width $Width -Height $Height -Vertex
}

function Connect {
    param(
        [string]$A,
        [string]$B,
        [string]$Id
    )

    Add-Edge -Id $Id -Source $A -Target $B -Style "endArrow=none;html=1;rounded=0;strokeWidth=1.1"
}

function Add-Relationship {
    param(
        [string]$Id,
        [string]$Label,
        [double]$X,
        [double]$Y,
        [double]$Width = 90,
        [double]$Height = 64,
        [switch]$Identifying
    )

    $style = if ($Identifying) {
        "rhombus;whiteSpace=wrap;html=1;fillColor=#ffe6cc;strokeColor=#d79b00;strokeWidth=2;fontStyle=1"
    } else {
        "rhombus;whiteSpace=wrap;html=1;fillColor=#ffe6cc;strokeColor=#d79b00;fontStyle=1"
    }

    Add-Cell -Id $Id -Value $Label -Style $style -X $X -Y $Y -Width $Width -Height $Height -Vertex

    if ($Identifying) {
        Add-Cell -Id "${Id}_inner" -Value '' -Style "rhombus;whiteSpace=wrap;html=1;fillColor=none;strokeColor=#d79b00;strokeWidth=1" -X ($X + 6) -Y ($Y + 6) -Width ($Width - 12) -Height ($Height - 12) -Vertex
    }
}

function Add-Isa {
    param(
        [string]$Id,
        [string]$Label,
        [double]$X,
        [double]$Y
    )

    Add-Cell -Id $Id -Value $Label -Style "shape=triangle;whiteSpace=wrap;html=1;fillColor=#fff2cc;strokeColor=#bf9000;direction=south;fontStyle=1" -X $X -Y $Y -Width 90 -Height 70 -Vertex
}

$entityW = 170
$entityH = 60

Add-Cell -Id 'title' -Value 'Hospital Management System (HMS) - EER Diagram with ISA, Weak Entities, and 19 Tables' -Style "text;html=1;strokeColor=none;fillColor=none;align=center;fontSize=22;fontStyle=1" -X 760 -Y 20 -Width 1550 -Height 30 -Vertex

# Top row
Add-Entity -Id 'doctorspecialization' -Label 'DOCTOR_SPECIALIZATION' -X 80 -Y 90 -Width 220 -Height 60
Add-Entity -Id 'doctor' -Label 'DOCTOR' -X 520 -Y 90 -Width $entityW -Height $entityH
Add-Entity -Id 'doctorslog' -Label 'DOCTOR_LOG' -X 760 -Y 80 -Width 170 -Height 70 -Kind 'weak'
Add-Entity -Id 'tblpatient' -Label 'TBLPATIENT (legacy)' -X 1010 -Y 90 -Width 190 -Height 60 -Kind 'legacy'
Add-Entity -Id 'tblmedicalhistory' -Label 'MEDICAL_HISTORY' -X 1240 -Y 80 -Width 190 -Height 70 -Kind 'weak'
Add-Entity -Id 'admin' -Label 'ADMIN' -X 1480 -Y 90 -Width 120 -Height 60
Add-Entity -Id 'tblcontactus' -Label 'TBLCONTACTUS (legacy)' -X 1660 -Y 90 -Width 220 -Height 60 -Kind 'legacy'

# Center left / user side
Add-Entity -Id 'users' -Label 'USER' -X 80 -Y 400 -Width 150 -Height 60
Add-Entity -Id 'userlog' -Label 'USER_LOG' -X 280 -Y 390 -Width 160 -Height 70 -Kind 'weak'
Add-Entity -Id 'patients' -Label 'PATIENT' -X 520 -Y 390 -Width 170 -Height 70

# Appointment flow
Add-Entity -Id 'appointment' -Label 'APPOINTMENT (staging)' -X 70 -Y 910 -Width 210 -Height 60 -Kind 'legacy'
Add-Entity -Id 'currentappointments' -Label 'APPOINTMENT (current_appointments)' -X 500 -Y 900 -Width 280 -Height 70
Add-Entity -Id 'prescriptions' -Label 'PRESCRIPTION' -X 470 -Y 1370 -Width 200 -Height 70
Add-Entity -Id 'pastappointments' -Label 'PAST_APPOINTMENT' -X 860 -Y 900 -Width 210 -Height 70 -Kind 'archive'
Add-Entity -Id 'paymenttransactions' -Label 'PAYMENT_TRANSACTION' -X 1180 -Y 900 -Width 240 -Height 70
Add-Entity -Id 'appointmenttransfers' -Label 'APPOINTMENT_TRANSFER' -X 980 -Y 1370 -Width 250 -Height 70 -Kind 'weak'

# Contact / feedback side
Add-Entity -Id 'contactqueries' -Label 'CONTACT_QUERY' -X 1690 -Y 430 -Width 210 -Height 70
Add-Entity -Id 'contactqueryhistory' -Label 'CONTACT_QUERY_HISTORY' -X 1970 -Y 430 -Width 250 -Height 70 -Kind 'weak'
Add-Entity -Id 'feedbackentries' -Label 'FEEDBACK_ENTRY' -X 1820 -Y 900 -Width 210 -Height 70

# Subtypes / specialization
Add-Entity -Id 'consultancypatient' -Label 'CONSULTANCY_PATIENT' -X 420 -Y 620 -Width 180 -Height 60 -Kind 'subtype'
Add-Entity -Id 'admittedpatient' -Label 'ADMITTED_PATIENT' -X 650 -Y 620 -Width 180 -Height 60 -Kind 'subtype'
Add-Isa -Id 'isa_patient' -Label 'ISA&#xa;d, partial&#xa;patientType' -X 575 -Y 500

Add-Entity -Id 'onlineappointment' -Label 'ONLINE_APPOINTMENT' -X 450 -Y 1140 -Width 180 -Height 60 -Kind 'subtype'
Add-Entity -Id 'walkinappointment' -Label 'WALK_IN_APPOINTMENT' -X 670 -Y 1140 -Width 180 -Height 60 -Kind 'subtype'
Add-Isa -Id 'isa_appointment' -Label 'ISA&#xa;d, partial&#xa;appointmentType' -X 615 -Y 1020

Add-Entity -Id 'userquery' -Label 'USER_QUERY' -X 1630 -Y 630 -Width 160 -Height 60 -Kind 'subtype'
Add-Entity -Id 'doctorquery' -Label 'DOCTOR_QUERY' -X 1840 -Y 630 -Width 180 -Height 60 -Kind 'subtype'
Add-Isa -Id 'isa_contact' -Label 'ISA&#xa;d, total&#xa;portal_type' -X 1760 -Y 520

# Key and special attributes
Add-Attribute -Id 'ds_id' -Label 'id' -X 20 -Y 105 -Width 50 -Height 28 -Kind 'key'
Add-Attribute -Id 'ds_name' -Label 'specialization' -X 120 -Y 30 -Width 110 -Height 30 -Kind 'candidate'
Add-Attribute -Id 'doc_id' -Label 'id' -X 470 -Y 105 -Width 50 -Height 28 -Kind 'key'
Add-Attribute -Id 'doc_name' -Label 'doctorName' -X 560 -Y 30 -Width 100 -Height 30
Add-Attribute -Id 'doc_email' -Label 'docEmail' -X 700 -Y 105 -Width 85 -Height 30 -Kind 'candidate'
Add-Attribute -Id 'tblp_id' -Label 'ID' -X 960 -Y 105 -Width 50 -Height 28 -Kind 'key'
Add-Attribute -Id 'mh_id' -Label 'ID' -X 1210 -Y 30 -Width 50 -Height 28 -Kind 'partialkey'
Add-Attribute -Id 'admin_id' -Label 'id' -X 1440 -Y 105 -Width 50 -Height 28 -Kind 'key'
Add-Attribute -Id 'contactus_id' -Label 'id' -X 1620 -Y 105 -Width 50 -Height 28 -Kind 'key'

Add-Attribute -Id 'user_id' -Label 'id' -X 30 -Y 415 -Width 50 -Height 28 -Kind 'key'
Add-Attribute -Id 'user_name' -Label 'fullName' -X 95 -Y 340 -Width 90 -Height 30
Add-Attribute -Id 'user_email' -Label 'email' -X 235 -Y 415 -Width 70 -Height 30 -Kind 'candidate'
Add-Attribute -Id 'address_composite' -Label 'ADDRESS' -X 10 -Y 500 -Width 90 -Height 30 -Kind 'composite'
Add-Attribute -Id 'user_address' -Label 'address' -X 0 -Y 560 -Width 80 -Height 28
Add-Attribute -Id 'user_city' -Label 'city' -X 100 -Y 560 -Width 65 -Height 28
Add-Attribute -Id 'userlog_uid' -Label 'uid' -X 250 -Y 330 -Width 55 -Height 28 -Kind 'partialkey'

Add-Attribute -Id 'patient_id' -Label 'id' -X 470 -Y 405 -Width 50 -Height 28 -Kind 'key'
Add-Attribute -Id 'patient_name' -Label 'patientName' -X 690 -Y 405 -Width 100 -Height 30
Add-Attribute -Id 'patient_status' -Label 'status' -X 540 -Y 480 -Width 70 -Height 28
Add-Attribute -Id 'patient_type' -Label 'patientType' -X 615 -Y 350 -Width 90 -Height 30 -Kind 'composite'
Add-Attribute -Id 'patient_age' -Label 'patientAge' -X 715 -Y 480 -Width 90 -Height 28 -Kind 'derived'
Add-Attribute -Id 'adm_date' -Label 'admissionDate' -X 705 -Y 700 -Width 110 -Height 30
Add-Attribute -Id 'is_emergency' -Label 'isEmergency' -X 610 -Y 740 -Width 95 -Height 30

Add-Attribute -Id 'appt_stage_id' -Label 'id' -X 20 -Y 925 -Width 50 -Height 28 -Kind 'key'
Add-Attribute -Id 'appt_current_id' -Label 'id' -X 450 -Y 915 -Width 50 -Height 28 -Kind 'key'
Add-Attribute -Id 'appt_date' -Label 'appointmentDate' -X 800 -Y 900 -Width 115 -Height 30
Add-Attribute -Id 'appt_type' -Label 'appointmentType' -X 570 -Y 845 -Width 115 -Height 30 -Kind 'composite'
Add-Attribute -Id 'schedule' -Label 'SCHEDULE' -X 470 -Y 1000 -Width 90 -Height 30 -Kind 'composite'
Add-Attribute -Id 'appt_time' -Label 'appointmentTime' -X 360 -Y 1045 -Width 120 -Height 28
Add-Attribute -Id 'visit_status' -Label 'visitStatus' -X 770 -Y 990 -Width 90 -Height 28
Add-Attribute -Id 'pay_status' -Label 'paymentStatus' -X 650 -Y 995 -Width 100 -Height 28
Add-Attribute -Id 'appt_online_note' -Label 'future: video link' -X 430 -Y 1220 -Width 120 -Height 28 -Kind 'derived'
Add-Attribute -Id 'appt_walkin_note' -Label 'future: token no' -X 760 -Y 1220 -Width 110 -Height 28 -Kind 'derived'

Add-Attribute -Id 'pres_id' -Label 'id' -X 420 -Y 1385 -Width 50 -Height 28 -Kind 'key'
Add-Attribute -Id 'vitals' -Label 'VITALS' -X 620 -Y 1290 -Width 75 -Height 30 -Kind 'composite'
Add-Attribute -Id 'temperature' -Label 'temperature' -X 500 -Y 1250 -Width 95 -Height 28
Add-Attribute -Id 'blood_pressure' -Label 'blood_pressure' -X 610 -Y 1235 -Width 120 -Height 28
Add-Attribute -Id 'pulse' -Label 'pulse' -X 740 -Y 1260 -Width 60 -Height 28
Add-Attribute -Id 'tests' -Label 'tests' -X 690 -Y 1445 -Width 65 -Height 28 -Kind 'multi'
Add-Attribute -Id 'medicines' -Label 'medicines' -X 520 -Y 1460 -Width 90 -Height 28 -Kind 'multi'

Add-Attribute -Id 'past_id' -Label 'id' -X 810 -Y 915 -Width 50 -Height 28 -Kind 'key'
Add-Attribute -Id 'archived_at' -Label 'archivedat' -X 910 -Y 995 -Width 90 -Height 28
Add-Attribute -Id 'orig_appt' -Label 'originalappointmentid' -X 1080 -Y 920 -Width 140 -Height 28 -Kind 'candidate'

Add-Attribute -Id 'pay_id' -Label 'id' -X 1130 -Y 915 -Width 50 -Height 28 -Kind 'key'
Add-Attribute -Id 'txn_ref' -Label 'transaction_ref' -X 1440 -Y 915 -Width 115 -Height 28 -Kind 'candidate'
Add-Attribute -Id 'amount' -Label 'amount' -X 1260 -Y 995 -Width 75 -Height 28

Add-Attribute -Id 'transfer_id' -Label 'id' -X 930 -Y 1385 -Width 50 -Height 28 -Kind 'partialkey'
Add-Attribute -Id 'from_type' -Label 'fromType' -X 1080 -Y 1460 -Width 80 -Height 28
Add-Attribute -Id 'to_type' -Label 'toType' -X 1180 -Y 1460 -Width 70 -Height 28

Add-Attribute -Id 'cq_id' -Label 'id' -X 1640 -Y 445 -Width 50 -Height 28 -Kind 'key'
Add-Attribute -Id 'cq_portal' -Label 'portal_type' -X 1750 -Y 360 -Width 90 -Height 30 -Kind 'composite'
Add-Attribute -Id 'cq_subject' -Label 'subject' -X 1910 -Y 445 -Width 80 -Height 28
Add-Attribute -Id 'cqh_id' -Label 'id' -X 1920 -Y 445 -Width 50 -Height 28 -Kind 'partialkey'
Add-Attribute -Id 'feedback_id' -Label 'id' -X 1770 -Y 915 -Width 50 -Height 28 -Kind 'key'
Add-Attribute -Id 'feedback_text' -Label 'feedback_text' -X 2035 -Y 915 -Width 105 -Height 28

Add-Attribute -Id 'uq_fk' -Label 'user_id FK' -X 1610 -Y 700 -Width 90 -Height 28
Add-Attribute -Id 'dq_fk' -Label 'doctor_id FK' -X 1865 -Y 700 -Width 95 -Height 28

# Attribute connections
Connect 'ds_id' 'doctorspecialization' 'e_ds_id'
Connect 'ds_name' 'doctorspecialization' 'e_ds_name'
Connect 'doc_id' 'doctor' 'e_doc_id'
Connect 'doc_name' 'doctor' 'e_doc_name'
Connect 'doc_email' 'doctor' 'e_doc_email'
Connect 'tblp_id' 'tblpatient' 'e_tblp_id'
Connect 'mh_id' 'tblmedicalhistory' 'e_mh_id'
Connect 'admin_id' 'admin' 'e_admin_id'
Connect 'contactus_id' 'tblcontactus' 'e_contactus_id'

Connect 'user_id' 'users' 'e_user_id'
Connect 'user_name' 'users' 'e_user_name'
Connect 'user_email' 'users' 'e_user_email'
Connect 'address_composite' 'users' 'e_address_comp'
Connect 'user_address' 'address_composite' 'e_user_address'
Connect 'user_city' 'address_composite' 'e_user_city'
Connect 'userlog_uid' 'userlog' 'e_userlog_uid'

Connect 'patient_id' 'patients' 'e_patient_id'
Connect 'patient_name' 'patients' 'e_patient_name'
Connect 'patient_status' 'patients' 'e_patient_status'
Connect 'patient_type' 'patients' 'e_patient_type'
Connect 'patient_age' 'patients' 'e_patient_age'
Connect 'adm_date' 'admittedpatient' 'e_adm_date'
Connect 'is_emergency' 'admittedpatient' 'e_is_emergency'

Connect 'appt_stage_id' 'appointment' 'e_appt_stage_id'
Connect 'appt_current_id' 'currentappointments' 'e_appt_current_id'
Connect 'appt_date' 'currentappointments' 'e_appt_date'
Connect 'appt_type' 'currentappointments' 'e_appt_type'
Connect 'schedule' 'currentappointments' 'e_schedule'
Connect 'appt_date' 'schedule' 'e_schedule_date'
Connect 'appt_time' 'schedule' 'e_schedule_time'
Connect 'visit_status' 'currentappointments' 'e_visit_status'
Connect 'pay_status' 'currentappointments' 'e_pay_status'
Connect 'appt_online_note' 'onlineappointment' 'e_online_note'
Connect 'appt_walkin_note' 'walkinappointment' 'e_walkin_note'

Connect 'pres_id' 'prescriptions' 'e_pres_id'
Connect 'vitals' 'prescriptions' 'e_vitals'
Connect 'temperature' 'vitals' 'e_temp'
Connect 'blood_pressure' 'vitals' 'e_bp'
Connect 'pulse' 'vitals' 'e_pulse'
Connect 'tests' 'prescriptions' 'e_tests'
Connect 'medicines' 'prescriptions' 'e_meds'

Connect 'past_id' 'pastappointments' 'e_past_id'
Connect 'archived_at' 'pastappointments' 'e_archived'
Connect 'orig_appt' 'pastappointments' 'e_orig_appt'

Connect 'pay_id' 'paymenttransactions' 'e_pay_id'
Connect 'txn_ref' 'paymenttransactions' 'e_txn_ref'
Connect 'amount' 'paymenttransactions' 'e_amount'

Connect 'transfer_id' 'appointmenttransfers' 'e_transfer_id'
Connect 'from_type' 'appointmenttransfers' 'e_from_type'
Connect 'to_type' 'appointmenttransfers' 'e_to_type'

Connect 'cq_id' 'contactqueries' 'e_cq_id'
Connect 'cq_portal' 'contactqueries' 'e_cq_portal'
Connect 'cq_subject' 'contactqueries' 'e_cq_subject'
Connect 'cqh_id' 'contactqueryhistory' 'e_cqh_id'
Connect 'feedback_id' 'feedbackentries' 'e_feedback_id'
Connect 'feedback_text' 'feedbackentries' 'e_feedback_text'
Connect 'uq_fk' 'userquery' 'e_uq_fk'
Connect 'dq_fk' 'doctorquery' 'e_dq_fk'

# ISA connections
Add-Edge -Id 'isa_patient_super' -Source 'patients' -Target 'isa_patient' -Style "endArrow=none;html=1;rounded=0;strokeWidth=1.4"
Add-Edge -Id 'isa_patient_consult' -Source 'isa_patient' -Target 'consultancypatient' -Style "endArrow=none;html=1;rounded=0;strokeWidth=1.4"
Add-Edge -Id 'isa_patient_admit' -Source 'isa_patient' -Target 'admittedpatient' -Style "endArrow=none;html=1;rounded=0;strokeWidth=1.4"

Add-Edge -Id 'isa_appt_super' -Source 'currentappointments' -Target 'isa_appointment' -Style "endArrow=none;html=1;rounded=0;strokeWidth=1.4"
Add-Edge -Id 'isa_appt_online' -Source 'isa_appointment' -Target 'onlineappointment' -Style "endArrow=none;html=1;rounded=0;strokeWidth=1.4"
Add-Edge -Id 'isa_appt_walkin' -Source 'isa_appointment' -Target 'walkinappointment' -Style "endArrow=none;html=1;rounded=0;strokeWidth=1.4"

Add-Edge -Id 'isa_cq_super' -Source 'contactqueries' -Target 'isa_contact' -Style "endArrow=none;html=1;rounded=0;strokeWidth=1.4"
Add-Edge -Id 'isa_cq_user' -Source 'isa_contact' -Target 'userquery' -Style "endArrow=none;html=1;rounded=0;strokeWidth=1.4"
Add-Edge -Id 'isa_cq_doctor' -Source 'isa_contact' -Target 'doctorquery' -Style "endArrow=none;html=1;rounded=0;strokeWidth=1.4"

# Relationships
Add-Relationship -Id 'r1' -Label 'has' -X 340 -Y 95
Add-Edge -Id 'r1a' -Source 'doctorspecialization' -Target 'r1' -Style "endArrow=none;html=1;rounded=0;strokeWidth=1.3"
Add-Edge -Id 'r1b' -Source 'doctor' -Target 'r1' -Style "endArrow=none;html=1;rounded=0;strokeWidth=1.3"
Add-Label -Id 'r1_card1' -Value '1' -X 295 -Y 88
Add-Label -Id 'r1_card2' -Value 'M' -X 448 -Y 88

Add-Relationship -Id 'r21' -Label 'identifies' -X 690 -Y 170 -Identifying
Add-Edge -Id 'r21a' -Source 'doctor' -Target 'r21' -Style "endArrow=none;html=1;rounded=0;strokeWidth=1.3"
Add-Edge -Id 'r21b' -Source 'doctorslog' -Target 'r21' -Style "endArrow=none;html=1;rounded=0;strokeWidth=1.3"
Add-Label -Id 'r21_card1' -Value '1' -X 655 -Y 160
Add-Label -Id 'r21_card2' -Value 'M' -X 835 -Y 160

Add-Relationship -Id 'r22' -Label 'treats' -X 760 -Y 255
Add-Edge -Id 'r22a' -Source 'doctor' -Target 'r22' -Style "endArrow=none;html=1;rounded=0;strokeWidth=1.3"
Add-Edge -Id 'r22b' -Source 'tblpatient' -Target 'r22' -Style "endArrow=none;html=1;rounded=0;strokeWidth=1.3"
Add-Label -Id 'r22_card1' -Value '1' -X 690 -Y 245
Add-Label -Id 'r22_card2' -Value 'M' -X 960 -Y 245

Add-Relationship -Id 'r23' -Label 'identifies' -X 1125 -Y 255 -Identifying
Add-Edge -Id 'r23a' -Source 'tblpatient' -Target 'r23' -Style "endArrow=none;html=1;rounded=0;strokeWidth=1.3"
Add-Edge -Id 'r23b' -Source 'tblmedicalhistory' -Target 'r23' -Style "endArrow=none;html=1;rounded=0;strokeWidth=1.3"
Add-Label -Id 'r23_card1' -Value '1' -X 1085 -Y 245
Add-Label -Id 'r23_card2' -Value 'M' -X 1325 -Y 245

Add-Relationship -Id 'r2' -Label 'registers as' -X 300 -Y 410 -Width 120
Add-Edge -Id 'r2a' -Source 'users' -Target 'r2' -Style "endArrow=none;html=1;rounded=0;strokeWidth=1.3"
Add-Edge -Id 'r2b' -Source 'patients' -Target 'r2' -Style "endArrow=none;html=1;rounded=0;strokeWidth=1.3"
Add-Label -Id 'r2_card1' -Value '1' -X 255 -Y 400
Add-Label -Id 'r2_card2' -Value 'M' -X 462 -Y 400

Add-Relationship -Id 'r20' -Label 'identifies' -X 205 -Y 300 -Identifying
Add-Edge -Id 'r20a' -Source 'users' -Target 'r20' -Style "endArrow=none;html=1;rounded=0;strokeWidth=1.3"
Add-Edge -Id 'r20b' -Source 'userlog' -Target 'r20' -Style "endArrow=none;html=1;rounded=0;strokeWidth=1.3"
Add-Label -Id 'r20_card1' -Value '1' -X 165 -Y 290
Add-Label -Id 'r20_card2' -Value 'M' -X 345 -Y 290

Add-Relationship -Id 'r3' -Label 'treats' -X 510 -Y 280
Add-Edge -Id 'r3a' -Source 'doctor' -Target 'r3' -Style "endArrow=none;html=1;rounded=0;strokeWidth=1.3"
Add-Edge -Id 'r3b' -Source 'patients' -Target 'r3' -Style "endArrow=none;html=1;rounded=0;strokeWidth=1.3"
Add-Label -Id 'r3_card1' -Value '1' -X 555 -Y 240
Add-Label -Id 'r3_card2' -Value 'M' -X 575 -Y 350

Add-Relationship -Id 'r4s' -Label 'pre-books' -X 135 -Y 760
Add-Edge -Id 'r4sa' -Source 'users' -Target 'r4s' -Style "endArrow=none;html=1;rounded=0;strokeWidth=1.1;dashed=1"
Add-Edge -Id 'r4sb' -Source 'appointment' -Target 'r4s' -Style "endArrow=none;html=1;rounded=0;strokeWidth=1.1;dashed=1"

Add-Relationship -Id 'r5s' -Label 'pre-assigns' -X 340 -Y 760 -Width 100
Add-Edge -Id 'r5sa' -Source 'doctor' -Target 'r5s' -Style "endArrow=none;html=1;rounded=0;strokeWidth=1.1;dashed=1"
Add-Edge -Id 'r5sb' -Source 'appointment' -Target 'r5s' -Style "endArrow=none;html=1;rounded=0;strokeWidth=1.1;dashed=1"

Add-Relationship -Id 'rstage' -Label 'staged into' -X 330 -Y 910 -Width 110
Add-Edge -Id 'rstagea' -Source 'appointment' -Target 'rstage' -Style "endArrow=none;html=1;rounded=0;strokeWidth=1.1;dashed=1"
Add-Edge -Id 'rstageb' -Source 'currentappointments' -Target 'rstage' -Style "endArrow=none;html=1;rounded=0;strokeWidth=1.1;dashed=1"

Add-Relationship -Id 'r4' -Label 'books' -X 260 -Y 760
Add-Edge -Id 'r4a' -Source 'users' -Target 'r4' -Style "endArrow=none;html=1;rounded=0;strokeWidth=1.3"
Add-Edge -Id 'r4b' -Source 'currentappointments' -Target 'r4' -Style "endArrow=none;html=1;rounded=0;strokeWidth=1.3"
Add-Label -Id 'r4_card1' -Value '1' -X 225 -Y 750
Add-Label -Id 'r4_card2' -Value 'M' -X 465 -Y 750

Add-Relationship -Id 'r5' -Label 'attends' -X 410 -Y 760
Add-Edge -Id 'r5a' -Source 'doctor' -Target 'r5' -Style "endArrow=none;html=1;rounded=0;strokeWidth=1.3"
Add-Edge -Id 'r5b' -Source 'currentappointments' -Target 'r5' -Style "endArrow=none;html=1;rounded=0;strokeWidth=1.3"
Add-Label -Id 'r5_card1' -Value '1' -X 470 -Y 720
Add-Label -Id 'r5_card2' -Value 'M' -X 560 -Y 830

Add-Relationship -Id 'r6' -Label 'has' -X 810 -Y 760
Add-Edge -Id 'r6a' -Source 'patients' -Target 'r6' -Style "endArrow=none;html=1;rounded=0;strokeWidth=1.3"
Add-Edge -Id 'r6b' -Source 'currentappointments' -Target 'r6' -Style "endArrow=none;html=1;rounded=0;strokeWidth=1.3"
Add-Label -Id 'r6_card1' -Value '1' -X 725 -Y 730
Add-Label -Id 'r6_card2' -Value 'M' -X 760 -Y 830

Add-Relationship -Id 'r7' -Label 'generates' -X 540 -Y 1240 -Width 100
Add-Edge -Id 'r7a' -Source 'currentappointments' -Target 'r7' -Style "endArrow=none;html=1;rounded=0;strokeWidth=1.3"
Add-Edge -Id 'r7b' -Source 'prescriptions' -Target 'r7' -Style "endArrow=none;html=1;rounded=0;strokeWidth=1.3"
Add-Label -Id 'r7_card1' -Value '1' -X 605 -Y 1220
Add-Label -Id 'r7_card2' -Value '1' -X 560 -Y 1335

Add-Relationship -Id 'r8' -Label 'writes' -X 340 -Y 1225
Add-Edge -Id 'r8a' -Source 'doctor' -Target 'r8' -Style "endArrow=none;html=1;rounded=0;strokeWidth=1.3"
Add-Edge -Id 'r8b' -Source 'prescriptions' -Target 'r8' -Style "endArrow=none;html=1;rounded=0;strokeWidth=1.3"
Add-Label -Id 'r8_card1' -Value '1' -X 430 -Y 680
Add-Label -Id 'r8_card2' -Value 'M' -X 405 -Y 1315

Add-Relationship -Id 'r9' -Label 'receives' -X 730 -Y 1225
Add-Edge -Id 'r9a' -Source 'patients' -Target 'r9' -Style "endArrow=none;html=1;rounded=0;strokeWidth=1.3"
Add-Edge -Id 'r9b' -Source 'prescriptions' -Target 'r9' -Style "endArrow=none;html=1;rounded=0;strokeWidth=1.3"
Add-Label -Id 'r9_card1' -Value '1' -X 700 -Y 680
Add-Label -Id 'r9_card2' -Value 'M' -X 720 -Y 1315

Add-Relationship -Id 'r10' -Label 'has' -X 1095 -Y 760
Add-Edge -Id 'r10a' -Source 'currentappointments' -Target 'r10' -Style "endArrow=none;html=1;rounded=0;strokeWidth=1.3"
Add-Edge -Id 'r10b' -Source 'paymenttransactions' -Target 'r10' -Style "endArrow=none;html=1;rounded=0;strokeWidth=1.3"
Add-Label -Id 'r10_card1' -Value '1' -X 1025 -Y 790
Add-Label -Id 'r10_card2' -Value 'M' -X 1260 -Y 790

Add-Relationship -Id 'r11' -Label 'makes' -X 1360 -Y 570
Add-Edge -Id 'r11a' -Source 'users' -Target 'r11' -Style "endArrow=none;html=1;rounded=0;strokeWidth=1.3"
Add-Edge -Id 'r11b' -Source 'paymenttransactions' -Target 'r11' -Style "endArrow=none;html=1;rounded=0;strokeWidth=1.3"
Add-Label -Id 'r11_card1' -Value '1' -X 1110 -Y 460
Add-Label -Id 'r11_card2' -Value 'M' -X 1410 -Y 760

Add-Relationship -Id 'r12' -Label 'identifies' -X 930 -Y 1135 -Identifying
Add-Edge -Id 'r12a' -Source 'currentappointments' -Target 'r12' -Style "endArrow=none;html=1;rounded=0;strokeWidth=1.3"
Add-Edge -Id 'r12b' -Source 'appointmenttransfers' -Target 'r12' -Style "endArrow=none;html=1;rounded=0;strokeWidth=1.3"
Add-Label -Id 'r12_card1' -Value '1' -X 925 -Y 1085
Add-Label -Id 'r12_card2' -Value 'M' -X 1065 -Y 1325

Add-Relationship -Id 'r13' -Label 'involved_in' -X 780 -Y 1260 -Width 110
Add-Edge -Id 'r13a' -Source 'doctor' -Target 'r13' -Style "endArrow=none;html=1;rounded=0;strokeWidth=1.3"
Add-Edge -Id 'r13b' -Source 'appointmenttransfers' -Target 'r13' -Style "endArrow=none;html=1;rounded=0;strokeWidth=1.3"

Add-Relationship -Id 'r14' -Label 'subject_of' -X 1140 -Y 1245 -Width 100
Add-Edge -Id 'r14a' -Source 'patients' -Target 'r14' -Style "endArrow=none;html=1;rounded=0;strokeWidth=1.3"
Add-Edge -Id 'r14b' -Source 'appointmenttransfers' -Target 'r14' -Style "endArrow=none;html=1;rounded=0;strokeWidth=1.3"

Add-Relationship -Id 'r24' -Label 'archived_as' -X 800 -Y 760 -Width 110
Add-Edge -Id 'r24a' -Source 'currentappointments' -Target 'r24' -Style "endArrow=none;html=1;rounded=0;strokeWidth=1.3"
Add-Edge -Id 'r24b' -Source 'pastappointments' -Target 'r24' -Style "endArrow=none;html=1;rounded=0;strokeWidth=1.3"
Add-Label -Id 'r24_card1' -Value '1' -X 820 -Y 840
Add-Label -Id 'r24_card2' -Value '1' -X 930 -Y 840

Add-Relationship -Id 'r15' -Label 'submits' -X 1580 -Y 420
Add-Edge -Id 'r15a' -Source 'users' -Target 'r15' -Style "endArrow=none;html=1;rounded=0;strokeWidth=1.3"
Add-Edge -Id 'r15b' -Source 'contactqueries' -Target 'r15' -Style "endArrow=none;html=1;rounded=0;strokeWidth=1.3"

Add-Relationship -Id 'r16' -Label 'submits' -X 1470 -Y 250
Add-Edge -Id 'r16a' -Source 'doctor' -Target 'r16' -Style "endArrow=none;html=1;rounded=0;strokeWidth=1.3"
Add-Edge -Id 'r16b' -Source 'contactqueries' -Target 'r16' -Style "endArrow=none;html=1;rounded=0;strokeWidth=1.3"

Add-Relationship -Id 'r17' -Label 'identifies' -X 1905 -Y 520 -Identifying
Add-Edge -Id 'r17a' -Source 'contactqueries' -Target 'r17' -Style "endArrow=none;html=1;rounded=0;strokeWidth=1.3"
Add-Edge -Id 'r17b' -Source 'contactqueryhistory' -Target 'r17' -Style "endArrow=none;html=1;rounded=0;strokeWidth=1.3"

Add-Relationship -Id 'r18' -Label 'submits' -X 1640 -Y 820
Add-Edge -Id 'r18a' -Source 'users' -Target 'r18' -Style "endArrow=none;html=1;rounded=0;strokeWidth=1.3"
Add-Edge -Id 'r18b' -Source 'feedbackentries' -Target 'r18' -Style "endArrow=none;html=1;rounded=0;strokeWidth=1.3"

Add-Relationship -Id 'r19' -Label 'submits' -X 1600 -Y 660
Add-Edge -Id 'r19a' -Source 'doctor' -Target 'r19' -Style "endArrow=none;html=1;rounded=0;strokeWidth=1.3"
Add-Edge -Id 'r19b' -Source 'feedbackentries' -Target 'r19' -Style "endArrow=none;html=1;rounded=0;strokeWidth=1.3"

# Standalone note for admin and legacy contact
Add-Cell -Id 'note_admin' -Value 'Standalone admin account table&#xa;(no strong FK participation in the md EER)' -Style "shape=note;whiteSpace=wrap;html=1;fillColor=#fff2cc;strokeColor=#d6b656" -X 1460 -Y 180 -Width 180 -Height 70 -Vertex
Add-Cell -Id 'note_tblcontactus' -Value 'Legacy public contact form table&#xa;kept separate from CONTACT_QUERY' -Style "shape=note;whiteSpace=wrap;html=1;fillColor=#fff2cc;strokeColor=#d6b656" -X 1680 -Y 180 -Width 190 -Height 70 -Vertex

$xml = @"
<mxfile host='app.diagrams.net' version='24.7.17'>
  <diagram id='hms-eer-v2' name='HMS EER Full'>
    <mxGraphModel dx='2400' dy='1400' grid='1' gridSize='10' guides='1' tooltips='1' connect='1' arrows='1' fold='1' page='1' pageScale='1' pageWidth='3400' pageHeight='2400'>
      <root>
        <mxCell id='0'/>
        <mxCell id='1' parent='0'/>
        $($cells -join "`n        ")
      </root>
    </mxGraphModel>
  </diagram>
</mxfile>
"@

$outPath = Join-Path $PSScriptRoot 'hms_eer_chen.drawio'
Set-Content -Path $outPath -Value $xml -Encoding UTF8
Write-Host "Generated $outPath"
