<?php
// patient_list.php - displays all patients from database
?>
<!DOCTYPE html>
<html>
<head>
    <title>Patient Records</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            font-family: system-ui, -apple-system, sans-serif;
            background: #eef2f6;
            padding: 2rem;
        }
        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            border-radius: 2rem;
            padding: 2rem;
            box-shadow: 0 12px 30px rgba(0,20,30,0.1);
        }
        h2 {
            color: #0b3b5c;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .header-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }
        .search-box {
            padding: 0.5rem 1rem;
            border: 1.5px solid #dae6f2;
            border-radius: 30px;
            width: 300px;
            font-size: 0.9rem;
        }
        .search-box:focus {
            outline: none;
            border-color: #1a6f9f;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th {
            text-align: left;
            padding: 1rem;
            color: #345a73;
            font-weight: 600;
            border-bottom: 2px solid #deeaf3;
        }
        td {
            padding: 1rem;
            border-bottom: 1px solid #e2e9f0;
        }
        .patient-row {
            cursor: pointer;
            transition: background 0.2s;
        }
        .patient-row:hover {
            background: #f5fafd;
        }
        .patient-name {
            font-weight: 600;
            color: #0b3b5c;
        }
        .patient-meta {
            font-size: 0.85rem;
            color: #60829e;
        }
        .pill {
            background: #ecf3fa;
            border-radius: 30px;
            padding: 0.3rem 1rem;
            font-size: 0.8rem;
            color: #15628b;
            display: inline-block;
        }
        .file-link {
            color: #0b3b5c;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            font-size: 0.85rem;
        }
        .file-link:hover {
            color: #1a6f9f;
            text-decoration: underline;
        }
        .no-data {
            text-align: center;
            padding: 3rem;
            color: #60829e;
        }
        .loading {
            text-align: center;
            padding: 2rem;
            color: #60829e;
        }
        .back-link {
            display: inline-block;
            margin-top: 1.5rem;
            color: #0b3b5c;
            text-decoration: none;
        }
        .back-link:hover {
            color: #1a6f9f;
        }
        .filters {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        .filter-btn {
            background: none;
            border: 1px solid #dae6f2;
            padding: 0.4rem 1rem;
            border-radius: 30px;
            cursor: pointer;
            color: #345a73;
        }
        .filter-btn.active {
            background: #0b3b5c;
            color: white;
            border-color: #0b3b5c;
        }
        @media (max-width: 700px) {
            body { padding: 1rem; }
            table { display: block; overflow-x: auto; }
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header-actions">
        <h2>
            <i class="fas fa-users" style="color: #0b3b5c;"></i>
            Patient Records
        </h2>
        <input type="text" class="search-box" id="searchInput" placeholder="Search patients..." onkeyup="filterPatients()">
    </div>

    <!-- Loading indicator -->
    <div id="loading" class="loading">
        <i class="fas fa-spinner fa-pulse"></i> Loading patients...
    </div>

    <!-- Patient table -->
    <div id="patientTable" style="display: none;">
        <table>
            <thead>
                <tr>
                    <th>Patient Name</th>
                    <th>Age/Gender</th>
                    <th>MRN</th>
                    <th>Next Follow-up</th>
                    <th>Added</th>
                    <th>File</th>
                </tr>
            </thead>
            <tbody id="patientBody">
            </tbody>
        </table>
    </div>

    <!-- No data message -->
    <div id="noData" class="no-data" style="display: none;">
        <i class="fas fa-folder-open" style="font-size: 3rem; margin-bottom: 1rem; color: #a0b8cc;"></i>
        <p>No patients found. Upload a new patient record to get started.</p>
    </div>

    <a href="dashboard.html" class="back-link">
        <i class="fas fa-arrow-left"></i> Back to Dashboard
    </a>
</div>

<script src="https://cdn.jsdelivr.net/npm/@supabase/supabase-js@2"></script>
<script>
    const SUPABASE_URL = 'https://dpippnqmuqsqhbstvqmm.supabase.co';
    const SUPABASE_ANON_KEY = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6ImRwaXBwbnFtdXFzcWhic3R2cW1tIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NzE3MDU4NzQsImV4cCI6MjA4NzI4MTg3NH0.rWuV1zLv5W-QPjmlvdMmrVn007-tqlDnbLq7WCjg7qU';
    const supabaseClient = supabase.createClient(SUPABASE_URL, SUPABASE_ANON_KEY);

    let allPatients = [];

    async function loadPatients() {
        try {
            const { data, error } = await supabaseClient
                .from('patients')
                .select('*')
                .order('created_at', { ascending: false });

            if (error) throw error;

            // Hide loading, show appropriate content
            document.getElementById('loading').style.display = 'none';
            
            if (data && data.length > 0) {
                allPatients = data;
                displayPatients(data);
                document.getElementById('patientTable').style.display = 'block';
            } else {
                document.getElementById('noData').style.display = 'block';
            }
        } catch (error) {
            console.error('Error loading patients:', error);
            document.getElementById('loading').innerHTML = `
                <i class="fas fa-exclamation-triangle" style="color: #dc3545;"></i>
                Error loading patients: ${error.message}
            `;
        }
    }

    function displayPatients(patients) {
        const tbody = document.getElementById('patientBody');
        tbody.innerHTML = patients.map(patient => {
            // Format date
            const addedDate = patient.created_at ? new Date(patient.created_at).toLocaleDateString() : 'Unknown';
            
            // Determine age/gender display
            const ageGender = [];
            if (patient.patient_age) ageGender.push(`${patient.patient_age}y`);
            if (patient.patient_gender) ageGender.push(patient.patient_gender);
            const ageGenderStr = ageGender.join(' · ') || '—';
            
            return `
                <tr class="patient-row" onclick="viewPatientProfile('${patient.id}')">
                    <td>
                        <div class="patient-name">${patient.patient_name || 'Unknown'}</div>
                        <div class="patient-meta">ID: ${patient.id.substring(0,8)}...</div>
                    </td>
                    <td>${ageGenderStr}</td>
                    <td>${patient.patient_mrn || '—'}</td>
                    <td>
                        ${patient.next_followup ? 
                            `<span class="pill"><i class="fas fa-calendar-check"></i> ${patient.next_followup}</span>` : 
                            '—'}
                    </td>
                    <td><span class="patient-meta">${addedDate}</span></td>
                    <td>
                        ${patient.uploaded_file_name ? 
                            `<a href="uploads/${patient.uploaded_file_name}" target="_blank" class="file-link" onclick="event.stopPropagation()">
                                <i class="fas fa-file-pdf" style="color: #dc3545;"></i> View
                            </a>` : 
                            '—'}
                    </td>
                </tr>
            `;
        }).join('');
    }

    function filterPatients() {
        const searchTerm = document.getElementById('searchInput').value.toLowerCase();
        
        if (!searchTerm) {
            displayPatients(allPatients);
            return;
        }

        const filtered = allPatients.filter(patient => 
            (patient.patient_name && patient.patient_name.toLowerCase().includes(searchTerm)) ||
            (patient.patient_mrn && patient.patient_mrn.toLowerCase().includes(searchTerm)) ||
            (patient.diagnosis && patient.diagnosis.toLowerCase().includes(searchTerm))
        );
        
        displayPatients(filtered);
        
        // Show message if no results
        if (filtered.length === 0) {
            document.getElementById('patientBody').innerHTML = `
                <tr>
                    <td colspan="6" style="text-align: center; padding: 2rem; color: #60829e;">
                        No patients matching "${searchTerm}"
                    </td>
                </tr>
            `;
        }
    }

    function viewPatientProfile(patientId) {
        window.location.href = `patient_profile.html?id=${patientId}`;
    }

    // Load patients when page loads
    document.addEventListener('DOMContentLoaded', loadPatients);
</script>
</body>
</html>