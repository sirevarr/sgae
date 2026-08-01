import sys
import json
import argparse
import os
from datetime import datetime

import re
from reportlab.lib.pagesizes import letter, landscape, A4
from reportlab.lib.units import inch, cm
from reportlab.lib import colors
from reportlab.platypus import (
    SimpleDocTemplate, Paragraph, Spacer, Table, TableStyle, Image, HRFlowable
)
from reportlab.lib.styles import getSampleStyleSheet, ParagraphStyle
from reportlab.lib.enums import TA_CENTER, TA_JUSTIFY, TA_RIGHT, TA_LEFT
from reportlab.pdfbase import pdfmetrics
from reportlab.pdfbase.ttfonts import TTFont

# MESES EN ESPAÑOL
MESES = {
    1: "ENERO", 2: "FEBRERO", 3: "MARZO", 4: "ABRIL",
    5: "MAYO", 6: "JUNIO", 7: "JULIO", 8: "AGOSTO",
    9: "SEPTIEMBRE", 10: "OCTUBRE", 11: "NOVIEMBRE", 12: "DICIEMBRE"
}

def get_base_styles():
    styles = getSampleStyleSheet()
    
    styles.add(ParagraphStyle(
        name='HeaderTitle',
        parent=styles['Normal'],
        alignment=TA_CENTER,
        fontName='Helvetica-Bold',
        fontSize=10,
        leading=12,
        spaceAfter=0
    ))
    
    styles.add(ParagraphStyle(
        name='DocTitle',
        parent=styles['Normal'],
        alignment=TA_CENTER,
        fontName='Helvetica-Bold',
        fontSize=14,
        leading=16,
        spaceAfter=15
    ))

    styles.add(ParagraphStyle(
        name='BodyJustified',
        parent=styles['Normal'],
        alignment=TA_JUSTIFY,
        fontName='Helvetica',
        fontSize=12,
        leading=14,
        spaceAfter=10
    ))

    styles.add(ParagraphStyle(
        name='Right',
        parent=styles['Normal'],
        alignment=TA_RIGHT,
        fontName='Helvetica',
        fontSize=12,
        leading=14,
        spaceAfter=10
    ))

    styles.add(ParagraphStyle(
        name='SignatureText',
        parent=styles['Normal'],
        alignment=TA_CENTER,
        fontName='Helvetica',
        fontSize=10,
        leading=13
    ))
    
    styles.add(ParagraphStyle(
        name='TableHeader',
        parent=styles['Normal'],
        alignment=TA_CENTER,
        fontName='Helvetica-Bold',
        fontSize=9,
        leading=11,
        textColor=colors.whitesmoke
    ))

    styles.add(ParagraphStyle(
        name='TableHeaderTiny',
        parent=styles['Normal'],
        alignment=TA_CENTER,
        fontName='Helvetica-Bold',
        fontSize=5,
        leading=6,
        textColor=colors.whitesmoke,
        wordWrap='Normal'
    ))

    styles.add(ParagraphStyle(
        name='TableCell',
        parent=styles['Normal'],
        alignment=TA_LEFT,
        fontName='Helvetica',
        fontSize=8,
        leading=10
    ))

    styles.add(ParagraphStyle(
        name='TableCellCenter',
        parent=styles['Normal'],
        alignment=TA_CENTER,
        fontName='Helvetica',
        fontSize=8,
        leading=10
    ))

    styles.add(ParagraphStyle(
        name='TableCellCenterTiny',
        parent=styles['Normal'],
        alignment=TA_CENTER,
        fontName='Helvetica',
        fontSize=5,
        leading=6
    ))

    styles.add(ParagraphStyle(
        name='TableCellSmall',
        parent=styles['Normal'],
        alignment=TA_LEFT,
        fontName='Helvetica',
        fontSize=7,
        leading=8
    ))

    styles.add(ParagraphStyle(
        name='TableHeaderSmall',
        parent=styles['Normal'],
        alignment=TA_CENTER,
        fontName='Helvetica-Bold',
        fontSize=8,
        leading=9,
        textColor=colors.whitesmoke
    ))

    return styles

def find_logo(name):
    # Buscar logos en public/imagenes/logos/ o public/imagenes/
    base_dir = os.path.dirname(os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
    possible_paths = [
        os.path.join(base_dir, 'public', 'imagenes', 'logos', name),
        os.path.join(base_dir, 'public', 'imagenes', name),
    ]
    for p in possible_paths:
        if os.path.exists(p):
            return p
    return None


def resolve_header_logos():
    left_path = r'C:\Users\sires\OneDrive\Desktop\FALTANTES\gesman\Logo\venezuela.png'
    right_path = r'C:\Users\sires\OneDrive\Desktop\FALTANTES\gesman\Logo\nelson.png'

    if os.path.exists(left_path):
        left_logo = left_path
    else:
        left_logo = find_logo('logo_miranda.jpg') or find_logo('carmen_ruiz.jpg') or find_logo('SGAE.png')

    if os.path.exists(right_path):
        right_logo = right_path
    else:
        right_logo = find_logo('escudo_venezuela.jpg') or find_logo('logo_escudo.png') or find_logo('venezuela.jpg')

    return left_logo, right_logo


def build_header_table(data, styles, landscape_mode=False, variant='default'):
    inst = data.get('institucion', {})
    
    nombre_inst = inst.get('nombre', 'UNIDAD EDUCATIVA ESTADAL “CARMEN RUIZ”').upper()
    codigo_plantel = inst.get('codigo_plantel', 'OD00221508').upper()
    ciudad = inst.get('ciudad', inst.get('municipio', 'CHARALLAVE – CRISTÓBAL ROJAS')).upper()
    estado = inst.get('estado', 'ESTADO BOLIVARIANO DE MIRANDA').upper()
    telefono_default = inst.get('telefono', '0239-2487847')
    telefono_asistencia = '0239.2487847'

    if variant == 'asistencia':
        header_elements = [
            Paragraph("REPÚBLICA BOLIVARIANA DE VENEZUELA", styles['HeaderTitle']),
            Paragraph("GOBERNACIÓN DEL ESTADO BOLIVARIANO DE MIRANDA", styles['HeaderTitle']),
            Paragraph("UNIDAD EDUCATIVA ESTADAL", styles['HeaderTitle']),
            Paragraph("<br/>", styles['HeaderTitle']),
            Paragraph("<font size='12'><b>\"CARMEN RUIZ\"</b></font>", styles['HeaderTitle']),
            Paragraph("<br/>", styles['HeaderTitle']),
            Paragraph("CÓDIGO PLANTEL: OD00221508", styles['HeaderTitle']),
            Paragraph("CHARALLAVE – CRISTÓBAL ROJAS", styles['HeaderTitle']),
            Paragraph(f"TELÉFONO: {telefono_asistencia}", styles['HeaderTitle']),
        ]
    else:
        header_elements = [
            Paragraph("REPÚBLICA BOLIVARIANA DE VENEZUELA", styles['HeaderTitle']),
            Paragraph(f"{estado}", styles['HeaderTitle']),
            Paragraph(f"{nombre_inst}", styles['HeaderTitle']),
            Paragraph(f"CÓDIGO PLANTEL: {codigo_plantel}", styles['HeaderTitle']),
            Paragraph(f"{ciudad}", styles['HeaderTitle']),
            Paragraph(f"TELÉFONO: {telefono_default}", styles['HeaderTitle']),
        ]

    logo_left_path, logo_right_path = resolve_header_logos()

    logo_izq = Image(logo_left_path, width=0.9*inch, height=0.9*inch) if logo_left_path else ''
    logo_der = Image(logo_right_path, width=0.9*inch, height=0.9*inch) if logo_right_path else ''

    if logo_izq: logo_izq.hAlign = 'LEFT'
    if logo_der: logo_der.hAlign = 'RIGHT'

    center_col_w = 7.0*inch if landscape_mode else 4.5*inch
    col_widths = [1.1*inch, center_col_w, 1.1*inch]

    table_data = [[logo_izq, header_elements, logo_der]]
    t = Table(table_data, colWidths=col_widths)
    t.setStyle(TableStyle([
        ('ALIGN', (0,0), (0,0), 'LEFT'),
        ('ALIGN', (1,0), (1,0), 'CENTER'),
        ('ALIGN', (2,0), (2,0), 'RIGHT'),
        ('VALIGN', (0,0), (-1,-1), 'MIDDLE'),
        ('LEFTPADDING', (0,0), (-1,-1), 0),
        ('RIGHTPADDING', (0,0), (-1,-1), 0),
        ('TOPPADDING', (0,0), (-1,-1), 0),
        ('BOTTOMPADDING', (0,0), (-1,-1), 0),
    ]))
    return t

# ─────────────────────────────────────────────
# 1. CONSTANCIA DE ESTUDIO
# ─────────────────────────────────────────────
def generar_constancia_estudio(data, output_file):
    doc = SimpleDocTemplate(
        output_file, pagesize=letter,
        leftMargin=1.0*inch, rightMargin=1.0*inch,
        topMargin=0.8*inch, bottomMargin=0.8*inch
    )
    styles = get_base_styles()
    story = []

    # Encabezado
    story.append(build_header_table(data, styles))
    story.append(Spacer(1, 0.35*inch))

    # Título
    story.append(Paragraph("CONSTANCIA DE ESTUDIO", styles['DocTitle']))
    story.append(Spacer(1, 0.8*inch))

    # Datos del alumno y curso
    est = data.get('estudiante', {})
    nombres = f"{est.get('nombres', '')} {est.get('apellidos', '')}".strip().upper()
    cedula = est.get('cedula', '')
    tipo_doc = est.get('tipo_documento', 'V')
    cedula_fmt = f"{tipo_doc}-{cedula}"
    edad = est.get('edad', '___')

    sec = data.get('seccion', {})
    grado = sec.get('nombre_grado', '___').upper()
    anio_escolar = data.get('anio', {}).get('descripcion', '2024-2025')

    p1 = (
        f"Quien Suscribe, Directivo de la Unidad Educativa Estadal “Carmen Ruiz”, "
        f"hace constar por medio de la presente que el (la) estudiante: "
        f"<b>{nombres}</b>, portador (a) de la Cédula de Identidad N° <b>{cedula_fmt}</b>, "
        f"de <b>{edad}</b> Años, y cursa el: <b>{grado}</b>, en esta Institución Educativa."
    )
    story.append(Paragraph(p1, styles['BodyJustified']))
    story.append(Spacer(1, 0.15*inch))

    p2 = f"Año escolar <b>{anio_escolar}</b>."
    story.append(Paragraph(p2, styles['BodyJustified']))
    story.append(Spacer(1, 0.3*inch))

    # Fecha de emisión
    dt = datetime.now()
    fecha_exp = (
        f"Constancia que se expide en la ciudad de CHARALLAVE a los <b>{dt.day}</b> días del mes de <b>{MESES.get(dt.month, '')}</b> del año <b>{dt.year}</b>."
    )
    story.append(Paragraph(fecha_exp, styles['BodyJustified']))
    story.append(Spacer(1, 1.0*inch))

    # Firma Directivo
    director = data.get('institucion', {}).get('director_nombre', 'DIRECTIVO').upper()
    story.append(Paragraph("Atentamente", styles['SignatureText']))
    story.append(Spacer(1, 1.0*inch))
    story.append(Paragraph("Directivo", styles['SignatureText']))
    story.append(Paragraph("__________________________________________", styles['SignatureText']))

    doc.build(story)

# ─────────────────────────────────────────────
# 2. CONSTANCIA DE BUENA CONDUCTA
# ─────────────────────────────────────────────
def generar_constancia_conducta(data, output_file):
    doc = SimpleDocTemplate(
        output_file, pagesize=letter,
        leftMargin=1.0*inch, rightMargin=1.0*inch,
        topMargin=0.8*inch, bottomMargin=0.8*inch
    )
    styles = get_base_styles()
    story = []

    story.append(build_header_table(data, styles))
    story.append(Spacer(1, 0.35*inch))

    story.append(Paragraph("CONSTANCIA DE BUENA CONDUCTA", styles['DocTitle']))
    story.append(Spacer(1, 0.4*inch))

    est = data.get('estudiante', {})
    nombres = f"{est.get('nombres', '')} {est.get('apellidos', '')}".strip().upper()
    cedula = est.get('cedula', '')
    tipo_doc = est.get('tipo_documento', 'V')
    cedula_fmt = f"{tipo_doc}-{cedula}"

    sec = data.get('seccion', {})
    grado = sec.get('nombre_grado', '___').upper()
    seccion_letra = sec.get('letra', 'A').upper()
    anio_escolar = data.get('anio', {}).get('descripcion', '')
    inst_nombre = data.get('institucion', {}).get('nombre', 'Unidad Educativa Estadal “Carmen Ruiz”')

    p1 = (
        f"Quien suscribe, Directivo de la <b>{inst_nombre}</b>, hace constar por medio de la presente que el (la) estudiante: "
        f"<b>{nombres}</b>, portador (a) de la Cédula de Identidad N° <b>{cedula_fmt}</b>, cursante del <b>{grado}</b>, "
        f"Sección <b>\"{seccion_letra}\"</b> durante el Año Escolar <b>{anio_escolar}</b>, ha demostrado una <b>EXCELENTE / BUENA CONDUCTA</b>, "
        f"cumpliendo satisfactoriamente con las normas de convivencia escolar y los deberes institucionales."
    )
    story.append(Paragraph(p1, styles['BodyJustified']))
    story.append(Spacer(1, 0.25*inch))

    dt = datetime.now()
    fecha_exp = (
        f"Constancia que se expide a solicitud de parte interesada en Charallave a los "
        f"<b>{dt.day}</b> días del mes de <b>{MESES.get(dt.month, '')}</b> del año <b>{dt.year}</b>."
    )
    story.append(Paragraph(fecha_exp, styles['BodyJustified']))
    story.append(Spacer(1, 1.2*inch))

    director = data.get('institucion', {}).get('director_nombre', 'DIRECTOR (A)').upper()
    story.append(Paragraph("Atentamente,", styles['SignatureText']))
    story.append(Spacer(1, 0.6*inch))
    story.append(Paragraph("__________________________________________", styles['SignatureText']))
    story.append(Paragraph(f"<b>{director}</b>", styles['SignatureText']))
    story.append(Paragraph("Directivo de la Institución", styles['SignatureText']))

    doc.build(story)

# ─────────────────────────────────────────────
# 3. CONSTANCIA DE PROSECUCIÓN
# ─────────────────────────────────────────────
def generar_constancia_prosecucion(data, output_file):
    doc = SimpleDocTemplate(
        output_file, pagesize=letter,
        leftMargin=1.0*inch, rightMargin=1.0*inch,
        topMargin=0.8*inch, bottomMargin=0.8*inch
    )
    styles = get_base_styles()
    story = []

    story.append(build_header_table(data, styles))
    story.append(Spacer(1, 0.35*inch))

    story.append(Paragraph("<b>CONSTANCIA DE PROSECUCIÓN</b>", styles['DocTitle']))
    story.append(Paragraph(f"<b>EN EL {data.get('seccion', {}).get('nombre_grado', 'GRADO').upper()}</b>", styles['DocTitle']))
    story.append(Spacer(1, 0.2*inch))

    est = data.get('estudiante', {})
    nombres = f"{est.get('nombres', '')} {est.get('apellidos', '')}".strip().upper()
    cedula = est.get('cedula', '')
    tipo_doc = est.get('tipo_documento', 'V')
    cedula_fmt = f"{tipo_doc}-{cedula}"

    sec = data.get('seccion', {})
    grado_cursado = sec.get('nombre_grado', '___').upper()
    grado_promovido = data.get('grado_promovido', f"{(int(re.sub('\D', '', sec.get('nombre_grado', '1')) or 1) + 1)}° Año / Grado").upper()
    anio_escolar = data.get('anio', {}).get('descripcion', '')
    inst_nombre = data.get('institucion', {}).get('nombre', 'Unidad Educativa Estadal “Carmen Ruiz”')

    p1 = (
        f"Quien suscribe, Directivo de la <b>{inst_nombre}</b>, certifica que el (la) estudiante: "
        f"<b>{nombres}</b>, titular de la Cédula Escolar N° <b>{cedula_fmt}</b>, cursó el <b>{grado_cursado}</b> "
        f"durante el periodo escolar <b>{anio_escolar}</b>, siendo promovido (a) al <b>{grado_promovido}</b>."
    )
    story.append(Paragraph(p1, styles['BodyJustified']))
    story.append(Spacer(1, 0.15*inch))

    dt = datetime.now()
    fecha_exp = (
        f"Constancia que se expide en <b>CHARALLAVE</b>, a los <b>{dt.day}</b> días del mes de <b>{MESES.get(dt.month, '')}</b> del año <b>{dt.year}</b>."
    )
    story.append(Paragraph(fecha_exp, styles['BodyJustified']))
    story.append(Spacer(1, 0.3*inch))

    director = data.get('institucion', {}).get('director_nombre', 'DIRECTOR (A)').upper()
    coordinador = data.get('institucion', {}).get('coordinador_nombre', 'COORDINADOR (A) PEDAGÓGICO').upper()

    t_firmas_data = [
        [
            Paragraph("____________________________________", styles['SignatureText']),
            Paragraph("____________________________________", styles['SignatureText'])
        ],
        [
            Paragraph(f"<b>{director}</b>", styles['SignatureText']),
            Paragraph(f"<b>{coordinador}</b>", styles['SignatureText'])
        ],
        [
            Paragraph("Directivo de la Institución", styles['SignatureText']),
            Paragraph("Coordinación Pedagógica", styles['SignatureText'])
        ]
    ]
    t_firmas = Table(t_firmas_data, colWidths=[3.2*inch, 3.2*inch])
    t_firmas.setStyle(TableStyle([
        ('ALIGN', (0,0), (-1,-1), 'CENTER'),
        ('VALIGN', (0,0), (-1,-1), 'TOP'),
    ]))
    story.append(t_firmas)

    doc.build(story)

# ─────────────────────────────────────────────
# 4. CONSTANCIA DE ASISTENCIA
# ─────────────────────────────────────────────
def generar_constancia_asistencia(data, output_file):
    doc = SimpleDocTemplate(
        output_file, pagesize=letter,
        leftMargin=1.0*inch, rightMargin=1.0*inch,
        topMargin=0.8*inch, bottomMargin=0.8*inch
    )
    styles = get_base_styles()
    story = []

    story.append(build_header_table(data, styles, variant='asistencia'))
    story.append(Spacer(1, 0.35*inch))

    dt = datetime.now()
    story.append(Paragraph(f"Charallave, {dt.day} de {MESES.get(dt.month, '')} de {dt.year}", styles['Right']))
    story.append(Spacer(1, 0.4*inch))

    story.append(Paragraph("<b><u>CONSTANCIA DE ASISTENCIA</u></b>", styles['DocTitle']))
    story.append(Spacer(1, 0.4*inch))

    est = data.get('estudiante', {})
    rep = data.get('representante', {}) or {}

    rep_nombres = f"{rep.get('nombres', '')} {rep.get('apellidos', '')}".strip().upper() or 'NOMBRE REPRESENTANTE'
    rep_cedula = rep.get('cedula_representante') or rep.get('cedula') or ''
    rep_cedula_fmt = f"{rep.get('tipo_documento', 'V')}-{rep_cedula}" if rep_cedula else ''

    nombres = f"{est.get('nombres', '')} {est.get('apellidos', '')}".strip().upper()
    cedula = est.get('cedula', '')
    tipo_doc = est.get('tipo_documento', 'V')
    cedula_fmt = f"{tipo_doc}-{cedula}"

    sec = data.get('seccion', {})
    grado_nombre = sec.get('nombre_grado', '___').upper()
    seccion_letra = sec.get('letra', 'A').upper()

    content_paragraph = (
        f"Quien suscribe, Directivo de la Unidad Educativa Estadal \"Carmen Ruiz\", "
        f"hace constar por medio de la presente que el(la) ciudadano(a): "
        f"<b>{rep_nombres}</b>, portador (a) de la cédula de identidad N°: <b>{rep_cedula_fmt}</b>, "
        f"Representante legal del estudiante: <b>{nombres}</b>, cédula identidad N°: <b>{cedula_fmt}</b>, "
        f"cursante del <b>{grado_nombre} \"{seccion_letra}\"</b>, asistió el día de hoy: <b>{dt.strftime('%d/%m/%Y')}</b> "
        f"para tratar asunto: <b>{data.get('motivo', '________________________')}</b>."
    )
    story.append(Paragraph(content_paragraph, styles['BodyJustified']))
    story.append(Spacer(1, 0.2*inch))

    story.append(Paragraph("Sin más a que hacer referencia, queda de Usted.", styles['BodyJustified']))
    story.append(Spacer(1, 0.4*inch))
    story.append(Paragraph("Atentamente,", styles['SignatureText']))
    story.append(Spacer(1, 0.6*inch))
    story.append(Paragraph("___________________________", styles['SignatureText']))
    story.append(Spacer(1, 0.3*inch))
    story.append(Paragraph("Directora (e)", styles['SignatureText']))

    doc.build(story)

# ─────────────────────────────────────────────
# 5. BOLETÍN DE CALIFICACIONES
# ─────────────────────────────────────────────
def generar_boletin(data, output_file):
    # Generador de boleta basado en la plantilla Gesman con cabecera A4 y tabla estructurada.
    doc = SimpleDocTemplate(
        output_file,
        pagesize=A4,
        leftMargin=2*cm,
        rightMargin=3*cm,
        topMargin=3*cm,
        bottomMargin=1*cm
    )
    story = []

    # Registrar Calibri si está disponible
    font_regular = 'Helvetica'
    try:
        pdfmetrics.registerFont(TTFont('Calibri', 'C:\\Windows\\Fonts\\calibri.ttf'))
        pdfmetrics.registerFont(TTFont('Calibri-Bold', 'C:\\Windows\\Fonts\\calibrib.ttf'))
        font_regular = 'Calibri'
        font_bold = 'Calibri-Bold'
    except Exception:
        pass

    def add_header_content(canvas, doc):
        canvas.saveState()
        canvas.setFont(font_regular, 7.5)
        header_y = A4[1] - 2.5*cm
        center_x = A4[0] / 2
        line_height = 7.5 * 1.2

        def draw_centered_text(text, y_offset):
            text_width = canvas.stringWidth(text, font_regular, 7.5)
            canvas.drawString(center_x - text_width / 2, header_y - y_offset, text)

        # Logotipos opcionales
        left_logo, right_logo = resolve_header_logos()
        if left_logo:
            canvas.drawImage(left_logo, 4*cm, header_y - 1.5*cm, width=1.5*cm, height=1.5*cm, preserveAspectRatio=True, mask='auto')
        if right_logo:
            canvas.drawImage(right_logo, A4[0] - 5.5*cm, header_y - 1.5*cm, width=1.5*cm, height=1.5*cm, preserveAspectRatio=True, mask='auto')

        draw_centered_text('República Bolivariana de Venezuela', 0)
        draw_centered_text('Gobernación del Estado Bolivariano de Miranda', line_height)
        draw_centered_text('Dirección General de Educación', 2*line_height)

        institucion = data.get('institucion', {})
        if institucion:
            codigo = institucion.get('codigo_plantel', 'OD00221508')
            nombre = institucion.get('nombre', 'Unidad Educativa Estadal “Carmen Ruiz”')
            ciudad = institucion.get('ciudad', institucion.get('municipio', 'CHARALLAVE – CRISTÓBAL ROJAS'))
            draw_centered_text(f'"{nombre}" COD-DEA: {codigo}', 3*line_height)
            draw_centered_text(f'{ciudad}', 4*line_height)

        draw_centered_text('BOLETA DE CALIFICACIONES', 5*line_height)
        canvas.restoreState()

    # Espacio inicial porque el encabezado está en el canvas
    story.append(Spacer(1, 1.8*cm))

    est = data.get('estudiante', {})
    sec = data.get('seccion', {})
    materias = data.get('materias', [])
    numero_momento = data.get('numero_momento')
    momentos = [m for m in [1, 2, 3] if numero_momento is None or m <= numero_momento]

    numero_lista = data.get('numero_lista') or ''
    nombres = f"{est.get('nombres', '')} {est.get('apellidos', '')}".upper()
    cedula = f"{est.get('tipo_documento', 'V')}-{est.get('cedula', '')}"
    grado_nombre = sec.get('nombre_grado', '').upper()
    seccion_letra = sec.get('letra', 'A').upper()
    gradosec = f'AÑO: {grado_nombre}' if grado_nombre else ''

    table_data = []
    base_cols = ['N° DE LISTA:', f'{numero_lista}', '', '', ''] if len(momentos) <= 2 else ['N° DE LISTA:', f'{numero_lista}', '', '', '', '']
    table_data.append(base_cols)
    table_data.append(['CÉDULA:', cedula, '', '', ''] if len(momentos) <= 2 else ['CÉDULA:', cedula, '', '', '', ''])
    table_data.append(['APELLIDOS:', est.get('apellidos', '').upper(), '', '', ''] if len(momentos) <= 2 else ['APELLIDOS:', est.get('apellidos', '').upper(), '', '', '', ''])
    table_data.append(['NOMBRES:', est.get('nombres', '').upper(), '', '', ''] if len(momentos) <= 2 else ['NOMBRES:', est.get('nombres', '').upper(), '', '', '', ''])
    table_data.append(['', '', '', '', ''] if len(momentos) <= 2 else ['', '', '', '', '', ''])
    table_data.append(['', '', f'{gradosec}     ', f'     SECCIÓN: "{seccion_letra}"', ''] if len(momentos) <= 2 else ['', '', f'{gradosec}     ', f'     SECCIÓN: "{seccion_letra}"', '', ''])
    table_data.append(['', '', '', '', ''] if len(momentos) <= 2 else ['', '', '', '', '', ''])

    header_row = ['', '']
    for m in [1, 2, 3]:
        if m in momentos:
            header_row.append('I MOM' if m == 1 else 'II MOM' if m == 2 else 'III MOM')
        else:
            header_row.append('')
    if len(momentos) > 2:
        header_row.append('FINAL')
    table_data.append(header_row)
    table_data.append(['', '', '', '', ''] if len(momentos) <= 2 else ['', '', '', '', '', ''])

    for materia in materias:
        row = [''] * (5 if len(momentos) <= 2 else 6)
        row[0] = materia.get('nombre', materia.get('siglas', '')).upper()
        notas = []
        for index, momento in enumerate(momentos, start=2):
            raw = materia.get(f'm{momento}')
            if raw is None or raw == '-':
                row[index] = ''
            else:
                if materia.get('tipo_evaluacion') == 'L':
                    row[index] = 'A' if raw == 1 else 'R'
                else:
                    row[index] = str(int(raw)) if isinstance(raw, (int, float)) else str(raw)
                notas.append(float(raw)) if isinstance(raw, (int, float)) else None
        if len(momentos) > 2:
            row[5] = str(int(round(sum(notas)/len(notas)))) if notas else ''
        table_data.append(row)

    table_data.append(['', '', '', '', ''] if len(momentos) <= 2 else ['', '', '', '', '', ''])
    table_data.append(['', '', '', '', ''] if len(momentos) <= 2 else ['', '', '', '', '', ''])
    table_data.append(['', '', '', '', ''] if len(momentos) <= 2 else ['', '', '', '', '', ''])
    if len(momentos) <= 2:
        table_data.append(['FIRMA:', '___________________________', 'SELLO:', '___________________________', ''])
    else:
        table_data.append(['FIRMA:', '___________________________', 'SELLO:', '___________________________', '', ''])

    obs_lines = ['_' * 100, '_' * 100, '_' * 100]
    obs_text = 'OBSERVACIONES:\n' + '\n'.join(obs_lines)
    if len(momentos) <= 2:
        table_data.append([obs_text, '', '', '', ''])
    else:
        table_data.append([obs_text, '', '', '', '', ''])

    col_widths = [2.04*cm, 3.95*cm] + [1.6*cm] * (len(momentos) + (1 if len(momentos) > 2 else 0))
    table = Table(table_data, colWidths=col_widths)
    table_style = [
        ('FONTNAME', (0, 0), (-1, -1), font_regular),
        ('FONTSIZE', (0, 0), (-1, -1), 7.5),
        ('VALIGN', (0, 0), (-1, -1), 'TOP'),
        ('ALIGN', (0, 0), (-1, -1), 'LEFT'),
        ('GRID', (0, 0), (-1, -1), 0, colors.white),
    ]
    for row_index in range(4):
        table_style.append(('ROWHEIGHT', (0, row_index), (-1, row_index), 0.25*cm))
    table_style.append(('ROWHEIGHT', (0, 4), (-1, 4), 0.3*cm))
    table_style.append(('ROWHEIGHT', (0, 5), (-1, 5), 0.25*cm))
    table_style.append(('ROWHEIGHT', (0, 6), (-1, 6), 0.3*cm))
    table_style.append(('ROWHEIGHT', (0, 7), (-1, 7), 0.25*cm))
    for i in range(len(materias)):
        row_index = 8 + i
        table_style.append(('ROWHEIGHT', (0, row_index), (-1, row_index), 0.25*cm))
    row_after = 8 + len(materias)
    table_style.append(('ROWHEIGHT', (0, row_after), (-1, row_after), 1.0*cm))
    table_style.append(('ROWHEIGHT', (0, row_after+1), (-1, row_after+1), 1.0*cm))
    table_style.append(('ROWHEIGHT', (0, row_after+2), (-1, row_after+2), 1.0*cm))
    table_style.append(('ROWHEIGHT', (0, row_after+3), (-1, row_after+3), 0.4*cm))
    table_style.append(('ROWHEIGHT', (0, row_after+4), (-1, row_after+4), 2*cm))
    table_style.append(('SPAN', (0, 8), (1, 8)))
    if len(momentos) <= 2:
        table_style.append(('SPAN', (0, row_after+4), (4, row_after+4)))
    else:
        table_style.append(('SPAN', (0, row_after+4), (5, row_after+4)))
    table.setStyle(TableStyle(table_style))
    story.append(table)
    story.append(Spacer(1, 2.5*cm))
    doc.build(story, onFirstPage=add_header_content, onLaterPages=add_header_content)

# ─────────────────────────────────────────────
# 6. LISTA DE SECCIÓN
# ─────────────────────────────────────────────
def generar_lista_seccion(data, output_file):
    doc = SimpleDocTemplate(
        output_file, pagesize=landscape(letter),
        leftMargin=0.5*inch, rightMargin=0.5*inch,
        topMargin=0.5*inch, bottomMargin=0.5*inch
    )
    styles = get_base_styles()
    story = []

    story.append(build_header_table(data, styles, landscape_mode=True))
    story.append(Spacer(1, 0.12*inch))

    sec = data.get('seccion', {})
    grado_str = f"{sec.get('nombre_grado', '')} - Sección \"{sec.get('letra', 'A')}\""
    docente = sec.get('docente_guia', 'Sin asignación').upper()
    anio = data.get('anio', {}).get('descripcion', '')

    story.append(Paragraph(f"LISTA DE ESTUDIANTES — {grado_str.upper()}", styles['DocTitle']))
    story.append(Spacer(1, 0.04*inch))
    story.append(Paragraph(f"AÑO ESCOLAR: {anio}", styles['TableCellSmall']))
    story.append(Paragraph(f"DOCENTE GUÍA: {docente}", styles['TableCellSmall']))
    story.append(Spacer(1, 0.1*inch))

    headers = [
        Paragraph("<b>N°</b>", styles['TableHeaderSmall']),
        Paragraph("<b>CÉDULA</b>", styles['TableHeaderSmall']),
        Paragraph("<b>APELLIDOS Y NOMBRES</b>", styles['TableHeaderSmall']),
        Paragraph("<b>SEXO</b>", styles['TableHeaderSmall']),
        Paragraph("<b>FECHA NAC.</b>", styles['TableHeaderSmall']),
        Paragraph("<b>EDAD</b>", styles['TableHeaderSmall']),
        Paragraph("<b>OBSERVACIONES / FIRMA</b>", styles['TableHeaderSmall']),
    ]
    rows = [headers]

    matriculas = data.get('matriculas', [])
    for idx, m in enumerate(matriculas, 1):
        est = m.get('estudiante', m)
        ced = f"{est.get('tipo_documento', 'V')}-{est.get('cedula', '')}"
        nombres = f"{est.get('apellidos', '')}, {est.get('nombres', '')}".upper()
        raw_genero = est.get('sexo') or est.get('genero')
        sexo = 'M'
        if raw_genero is not None:
            s = str(raw_genero).strip().upper()
            if s.startswith('F'):
                sexo = 'F'
            elif s.startswith('M'):
                sexo = 'M'
            else:
                sexo = s[:1] if len(s) > 0 else 'M'
        fnac = est.get('fecha_nacimiento', '')
        edad = str(est.get('edad', ''))

        rows.append([
            Paragraph(str(idx), styles['TableCellCenter']),
            Paragraph(ced, styles['TableCellCenter']),
            Paragraph(nombres, styles['TableCell']),
            Paragraph(sexo, styles['TableCellCenter']),
            Paragraph(fnac, styles['TableCellCenter']),
            Paragraph(edad, styles['TableCellCenter']),
            Paragraph("", styles['TableCell']),
        ])

    table = Table(rows, colWidths=[0.4*inch, 1.2*inch, 3.6*inch, 0.6*inch, 1.0*inch, 0.6*inch, 2.4*inch])
    table.setStyle(TableStyle([
        ('FONTNAME', (0, 0), (-1, -1), 'Helvetica'),
        ('FONTSIZE', (0, 0), (-1, -1), 8),
        ('BACKGROUND', (0,0), (-1,0), colors.HexColor("#1C355B")),
        ('TEXTCOLOR', (0,0), (-1,0), colors.whitesmoke),
        ('GRID', (0,0), (-1,-1), 0.35, colors.HexColor("#C0C0C0")),
        ('VALIGN', (0,0), (-1,-1), 'MIDDLE'),
        ('ALIGN', (0,0), (-1,0), 'CENTER'),
        ('ALIGN', (0,1), (1,-1), 'CENTER'),
        ('ALIGN', (2,1), (2,-1), 'LEFT'),
        ('LEFTPADDING', (0,0), (-1,-1), 2),
        ('RIGHTPADDING', (0,0), (-1,-1), 2),
        ('TOPPADDING', (0,0), (-1,-1), 2),
        ('BOTTOMPADDING', (0,0), (-1,-1), 2),
        ('ROWBACKGROUNDS', (0,1), (-1,-1), [colors.white, colors.HexColor("#F7F9FC")]),
    ]))
    story.append(table)
    story.append(Spacer(1, 0.12*inch))

    conteo = data.get('conteo', {})
    v = conteo.get('varones', 0)
    h = conteo.get('hembras', 0)
    tot = conteo.get('total', len(matriculas))
    c_text = (
        f"<b>RESUMEN DE MATRÍCULA:</b> Masculino: {v} | Femenino: {h} | Total Estudiantes: {tot} "
        f"&nbsp;&nbsp;&nbsp;&nbsp; <b>Docente Guía:</b> {docente}"
    )
    story.append(Paragraph(c_text, styles['TableCellSmall']))
    story.append(Spacer(1, 0.1*inch))

    doc.build(story)

# ─────────────────────────────────────────────
# 7. RESUMEN DE SECCIÓN (LIBRO DE NOTAS)
# ─────────────────────────────────────────────
def generar_resumen_seccion(data, output_file):
    doc = SimpleDocTemplate(
        output_file, pagesize=landscape(letter),
        leftMargin=0.4*inch, rightMargin=0.4*inch,
        topMargin=0.4*inch, bottomMargin=0.4*inch
    )
    styles = get_base_styles()
    story = []

    story.append(build_header_table(data, styles, landscape_mode=True))
    story.append(Spacer(1, 0.15*inch))

    sec = data.get('seccion', {})
    grado_str = f"{sec.get('nombre_grado', '')} - Sección \"{sec.get('letra', 'A')}\""
    anio = data.get('anio', {}).get('descripcion', '')
    momento_str = data.get('tipo_boletin', 'RESUMEN FINAL DE SECCIÓN').upper()

    story.append(Paragraph(f"RESUMEN DE CALIFICACIONES — {grado_str.upper()} ({momento_str})", styles['DocTitle']))
    story.append(Spacer(1, 0.04*inch))
    docente = sec.get('docente_guia', 'Sin asignación').upper()
    story.append(Paragraph(f"AÑO ESCOLAR: {anio}", styles['TableCellSmall']))
    story.append(Paragraph(f"DOCENTE GUÍA: {docente}", styles['TableCellSmall']))
    story.append(Spacer(1, 0.1*inch))

    materias = data.get('materias', [])
    num_mats = len(materias)
    numero_momento = data.get('numero_momento')
    momentos = [m for m in [1, 2, 3] if numero_momento is None or m <= numero_momento]
    tipo_evaluacion_map = {m.get('siglas', ''): m.get('tipo_evaluacion', 'N') for m in materias}

    headers = [Paragraph("<b>N°</b>", styles['TableHeaderSmall']), Paragraph("<b>CÉDULA / ESTUDIANTE</b>", styles['TableHeaderSmall'])]
    for m in materias:
        siglas = (m.get('siglas', m.get('nombre', '')) or '')[:2].upper()
        for momento in momentos:
            headers.append(Paragraph(f"<b>{siglas}{momento}</b>", styles['TableHeaderTiny']))
        headers.append(Paragraph("<b>DEF</b>", styles['TableHeaderTiny']))
    headers.append(Paragraph("<b>PROM.</b>", styles['TableHeaderTiny']))
    headers.append(Paragraph("<b>RES.</b>", styles['TableHeaderTiny']))

    rows = [headers]

    est_list = data.get('estudiantesData', [])
    for idx, est in enumerate(est_list, 1):
        ced = f"{est.get('tipo_doc', 'V')}-{est.get('cedula', '')}"
        nom = f"{est.get('apellidos', '')}, {est.get('nombres', '')}".upper()
        col_est = Paragraph(f"<b>{ced}</b><br/>{nom}", styles['TableCellSmall'])

        row = [Paragraph(str(idx), styles['TableCellCenterTiny']), col_est]
        evs = est.get('evaluaciones', {})

        notas_sum = 0
        notas_count = 0
        todo_aprobado = True
        tiene_datos = False
        nota_minima = data.get('nota_minima', 10)

        for m in materias:
            sig = m.get('siglas', '')
            tipo_eval = tipo_evaluacion_map.get(sig, 'N')
            nota_vals = evs.get(sig, '-')
            materia_vals = []

            def normalize_nota_values(values):
                if isinstance(values, dict):
                    normalized = {}
                    for k, v in values.items():
                        try:
                            kk = int(k)
                        except Exception:
                            kk = k
                        if isinstance(v, str):
                            v_strip = v.replace(',', '.').strip()
                            try:
                                v_conv = float(v_strip) if v_strip != '-' else '-'
                            except Exception:
                                v_conv = v
                        else:
                            v_conv = v
                        normalized[kk] = v_conv
                    return normalized

                if isinstance(values, list):
                    normalized = {}
                    for item in values:
                        if isinstance(item, dict):
                            mom = item.get('numero_momento') or item.get('momento') or item.get('momento_evaluacion')
                            nota = item.get('nota') if 'nota' in item else item.get('valor')
                            if mom is not None:
                                try:
                                    kk = int(mom)
                                except Exception:
                                    kk = mom
                                if isinstance(nota, str):
                                    nota_strip = nota.replace(',', '.').strip()
                                    try:
                                        nota_conv = float(nota_strip) if nota_strip != '-' else '-'
                                    except Exception:
                                        nota_conv = nota
                                else:
                                    nota_conv = nota
                                normalized[kk] = nota_conv
                    return normalized

                return {mom: '-' for mom in momentos}

            nota_vals = normalize_nota_values(nota_vals)

            for momento in momentos:
                nota_val = nota_vals.get(momento, '-')
                if isinstance(nota_val, (int, float)):
                    materia_vals.append(nota_val)
                    if tipo_eval == 'L':
                        display_val = 'A' if nota_val == 1 else 'R'
                    else:
                        display_val = str(round(nota_val))
                elif nota_val is None or nota_val == '-':
                    display_val = '-'
                else:
                    display_val = str(nota_val)
                row.append(Paragraph(display_val, styles['TableCellCenterTiny']))

            if materia_vals:
                def_nota = round(sum(materia_vals) / len(materia_vals), 2)
                if tipo_eval == 'L':
                    def_display = 'A' if def_nota == 1 else 'R'
                    res = def_display
                else:
                    def_display = str(def_nota)
                    res = 'A' if def_nota >= nota_minima else 'R'
                row.append(Paragraph(def_display, styles['TableCellCenterTiny']))

                notas_sum += def_nota
                notas_count += 1
                tiene_datos = True
                if res == 'R':
                    todo_aprobado = False
            else:
                row.append(Paragraph('-', styles['TableCellCenterTiny']))
                todo_aprobado = False

        prom_str = str(round(notas_sum / notas_count, 2)) if notas_count > 0 else '-'
        cond_final = 'AP' if (tiene_datos and todo_aprobado) else ('RP' if tiene_datos else '—')

        row.append(Paragraph(prom_str, styles['TableCellCenterTiny']))
        row.append(Paragraph(f"<b>{cond_final}</b>", styles['TableCellCenterTiny']))
        rows.append(row)

    # Ancho dinámico de columnas
    page_width = landscape(letter)[0]
    available_width = page_width - 0.4*inch - 0.4*inch
    denom = num_mats * (len(momentos) + 2)
    left_width = 0.30 * inch
    student_width = 1.6 * inch
    side_width = 0.25 * inch
    min_materia_width = 0.24 * inch
    max_materia_width = 0.75 * inch

    if denom > 0:
        materia_available = available_width - left_width - student_width - 2 * side_width
        largura_materia = max(min_materia_width, min(max_materia_width, materia_available / denom))
        total_width = left_width + student_width + 2 * side_width + denom * largura_materia
        if total_width > available_width:
            student_width = 1.4 * inch
            side_width = 0.20 * inch
            materia_available = available_width - left_width - student_width - 2 * side_width
            largura_materia = max(min_materia_width, materia_available / denom)
            largura_materia = min(largura_materia, max_materia_width)
    else:
        largura_materia = 0.4 * inch

    col_widths = [left_width, student_width] + [largura_materia] * denom + [side_width, side_width]

    table = Table(rows, colWidths=col_widths, repeatRows=1)
    table.setStyle(TableStyle([
        ('FONTNAME', (0, 0), (-1, -1), 'Helvetica'),
        ('FONTSIZE', (0, 0), (-1, -1), 7),
        ('BACKGROUND', (0,0), (-1,0), colors.HexColor("#1C355B")),
        ('TEXTCOLOR', (0,0), (-1,0), colors.whitesmoke),
        ('GRID', (0,0), (-1,-1), 0.25, colors.grey),
        ('VALIGN', (0,0), (-1,-1), 'MIDDLE'),
        ('ALIGN', (0,0), (-1,0), 'CENTER'),
        ('ALIGN', (0,1), (0,-1), 'CENTER'),
        ('ALIGN', (1,1), (1,-1), 'LEFT'),
        ('ALIGN', (2,1), (-1,-1), 'CENTER'),
        ('LEFTPADDING', (0,0), (-1,-1), 2),
        ('RIGHTPADDING', (0,0), (-1,-1), 2),
        ('TOPPADDING', (0,0), (-1,-1), 2),
        ('BOTTOMPADDING', (0,0), (-1,-1), 2),
        ('ROWBACKGROUNDS', (0,1), (-1,-1), [colors.white, colors.HexColor("#F9FAFC")]),
    ]))
    story.append(table)

    doc.build(story)


def main():
    parser = argparse.ArgumentParser(description="Generador de PDF con ReportLab para SGAE")
    parser.add_argument("--tipo", required=True, help="Tipo de documento")
    parser.add_argument("--json", required=True, help="Ruta al archivo JSON con los datos")
    parser.add_argument("--output", required=True, help="Ruta de salida del PDF generado")

    args = parser.parse_args()

    if not os.path.exists(args.json):
        print(f"Error: El archivo JSON {args.json} no existe.", file=sys.stderr)
        sys.exit(1)

    with open(args.json, 'r', encoding='utf-8') as f:
        data = json.load(f)

    tipo = args.tipo.lower()

    if tipo == 'constancia_estudio':
        generar_constancia_estudio(data, args.output)
    elif tipo == 'constancia_conducta':
        generar_constancia_conducta(data, args.output)
    elif tipo == 'constancia_prosecucion':
        generar_constancia_prosecucion(data, args.output)
    elif tipo == 'constancia_asistencia':
        generar_constancia_asistencia(data, args.output)
    elif tipo == 'boletin':
        generar_boletin(data, args.output)
    elif tipo == 'lista_seccion':
        generar_lista_seccion(data, args.output)
    elif tipo == 'resumen_seccion':
        generar_resumen_seccion(data, args.output)
    else:
        print(f"Error: Tipo de documento desconocido '{tipo}'", file=sys.stderr)
        sys.exit(1)

    print("PDF generado exitosamente.")

if __name__ == '__main__':
    main()
