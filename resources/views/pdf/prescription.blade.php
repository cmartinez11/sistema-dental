<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Receta Médica - Clínica Dental</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #333;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
        }

        .header {
            text-align: center;
            border-bottom: 2px solid #4f46e5;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }

        .header h1 {
            color: #4f46e5;
            margin: 0;
            font-size: 24px;
        }

        .header p {
            margin: 5px 0 0;
            color: #666;
            font-size: 14px;
        }

        .info-box {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }

        .info-box table {
            width: 100%;
            border-collapse: collapse;
        }

        .info-box td {
            padding: 4px 0;
            font-size: 14px;
        }

        .section-title {
            font-weight: bold;
            color: #1f2937;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 5px;
            margin-top: 20px;
            margin-bottom: 10px;
            font-size: 16px;
        }

        .content-text {
            font-size: 14px;
            white-space: pre-line;
            background: #fff;
            padding: 10px;
            border-left: 4px solid #4f46e5;
        }

        .footer {
            margin-top: 60px;
            text-align: center;
            font-size: 13px;
            color: #666;
        }

        .signature-line {
            margin-top: 50px;
            border-top: 1px solid #333;
            width: 250px;
            margin-left: auto;
            margin-right: auto;
            text-align: center;
            padding-top: 5px;
        }
    </style>
</head>

<body>

    <!-- Encabezado de la Clínica -->
    <div class="header">
        <h1>CLÍNICA DENTAL ESPECIALIZADA</h1>
        <p>Jr. Los Proceres S/N - San Juan de Lurigancho, Lima</p>
        <p>Teléfono: (01) 555-1234 | Celular: 987 654 321</p>
    </div>

    <!-- Datos del Paciente y Cita -->
    <div class="info-box">
        <table>
            <tr>
                <td><strong>Paciente:</strong> {{ $medicalRecord->patient->first_name }}
                    {{ $medicalRecord->patient->last_name }}
                </td>
                <td><strong>Fecha:</strong> {{ $medicalRecord->created_at->format('d/m/Y H:i') }}</td>
            </tr>
            <tr>
                <td><strong>Odontólogo:</strong> Dr. {{ $medicalRecord->dentist->user->name ?? 'Especialista' }}</td>
                <td><strong>CMP:</strong> {{ $medicalRecord->dentist->cmp_number ?? 'N/A' }}</td>
            </tr>
        </table>
    </div>

    <!-- Diagnóstico -->
    <div class="section-title">Diagnóstico Clínico</div>
    <div class="content-text">
        {{ $medicalRecord->diagnosis }}
    </div>

    <!-- Receta / Tratamiento -->
    <div class="section-title">Receta / Indicaciones Médicas</div>
    <div class="content-text" style="border-left-color: #059669;">
        {!! nl2br(e($medicalRecord->prescription ?? 'No se registraron medicamentos en esta sesión.')) !!}
    </div>

    <!-- Firma del Odontólogo -->
    <div class="footer">
        <div class="signature-line">
            Firma y Sello del Odontólogo
        </div>
        <p style="margin-top: 10px; font-size: 11px; color: #9ca3af;">Documento generado digitalmente por el sistema de
            gestión dental.</p>
    </div>

</body>

</html>