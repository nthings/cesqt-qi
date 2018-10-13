<?php

/**
 * cesqt Tabbed Settings Page
 */

add_action( 'admin_menu', 'cesqt_qi_admin' );

function cesqt_qi_admin() {
    $hook = add_menu_page(
        'Cuestionario Calidad de Vida Laboral',     // page title
        'CESQT',     // menu title
        'cesqt',   // capability
        'cuestionario-cesqt',     // menu slug
		'render_cesqt_qi_admin', // callback function
		'dashicons-universal-access'
    );
}


function cesqt_admin_tabs( $current = 'INFORMACION' ) {
    global $wpdb;
    $table_grupos = $wpdb->prefix . "cesqt_grupos";
    $grupos = $wpdb->get_results(
        "SELECT * FROM $table_grupos", 
        'ARRAY_A'
    );

    echo '<div id="icon-themes" class="icon32"><br></div>';
    echo '<h2 class="nav-tab-wrapper">';
    foreach( $grupos as $index => $row ){
        $class = ( $row['nombre'] == $current ) ? ' nav-tab-active' : '';
        echo '<a class="nav-tab'. $class. '" href="?page=cuestionario-cesqt&tab=' . $row['nombre'] . '">' . $row['nombrelimpio'] . '</a>';
    }
    echo '</h2>';
}

function render_cesqt_qi_admin() {
    global $title;
    $current_user = wp_get_current_user();
    $org_id = get_user_meta($current_user->ID, 'hash', true);
	?>
	
	<div class="wrap">
        <h2><?php echo $title; ?></h2>
        <p>Tu link para compartir el cuestionario de resiliencia a tus empleados es: </p>
        <a href="/cuestionario-cesqt/?org_id=<?php echo $org_id;?>"><?php echo get_site_url();?>/cuestionario-cesqt/?org_id=<?php echo $org_id;?></a>
		
		<?php			
            if ( isset ( $_GET['tab'] ) ) cesqt_admin_tabs($_GET['tab']); else cesqt_admin_tabs('INFORMACION');
            if ( isset ( $_GET['tab'] ) ) $tab = $_GET['tab']; else $tab = 'INFORMACION'; 
            
            cesqt_qi_admin_graficas($tab, $org_id);
		?>
		

	</div>
<?php
}

function cesqt_qi_admin_graficas($grupo, $org_id) {
    // labels = ['Bajo', 'Alto'];
    // data = [4, 1];
    global $wpdb;
    $table_grupos = $wpdb->prefix . "cesqt_grupos";
    $title = $wpdb->get_var("SELECT nombrelimpio FROM $table_grupos WHERE nombre='$grupo' ");
    echo '<h1>' . $title . '</h1>';
    if ($grupo == 'INFORMACION' || $grupo == 'ALCOHOLISMO_Y_TABAQUISMO') {
        construir_datos_grafica_especial($grupo, $org_id);
    } else {
        $chart_data = construir_datos_grafica($grupo, $org_id);
        $variables = array(
            '%LABELS%',
            '%DATA%',
            '%COLORS%',
        );
        $values = array(
            json_encode($chart_data['labels']), 
            json_encode($chart_data['data']),
            json_encode($chart_data['colors']),
        );
        echo str_replace($variables, $values, file_get_contents(  CESQT_PLUGIN_PATH . "templates/cesqt_qi_admin_graficas.html" ));
    }
}

function construir_datos_grafica_especial($grupo, $org_id) {
    global $wpdb;
    $table_resultados = $wpdb->prefix . "cesqt_resultados RS";
    $table_preguntas = $wpdb->prefix . "cesqt_preguntas P";
    $table_registros = $wpdb->prefix . "cesqt_registros R";
    if ($grupo == 'INFORMACION') {

        ?>
        <div id="result-container" style="width: 100%;">
            <h2>Sexo</h2>
            <div>
                <canvas id="sexo" width="400" height="400"></canvas>
            </div>
            <h2>Edad</h2>
            <p><?php echo get_promedio_pregunta($org_id, 2);?></p>

            <h2>Estado Civil</h2>
            <div>
                <canvas id="estado_civil" width="400" height="400"></canvas>
            </div>

            <h2>Hijos</h2>
            <p>Número de hijos promedio: <?php echo get_promedio_pregunta($org_id, 4);?></p>
            <p>Número de hijos viviendo en casa promedio: <?php echo get_promedio_pregunta($org_id, 5);?></p>

            <h2>Tipo De Contrato</h2>
            <div>
                <canvas id="tipo_contrato" width="400" height="400"></canvas>
            </div>

            <h2>Años De Experiencia</h2>
            <div>
                <canvas id="años_de_experiencia" width="400" height="400"></canvas>
            </div>

            <h2>Último Grado de Estudio</h2>
            <div>
                <canvas id="grados_estudio" width="400" height="400"></canvas>
            </div>
        </div>

        <script>
            var ctx_sexo = document.getElementById("sexo").getContext("2d");
            ctx_sexo.canvas.height = 400;
            ctx_sexo.canvas.width = document.getElementById('result-container').innerWidth;
            var grafica_sexo = new Chart(ctx_sexo, {
                type: 'pie',
                data: {
                    labels:  ['Masculino', 'Femenino'],
                    datasets: [{
                        data: JSON.parse('<?php echo json_encode(array(get_resultados_pregunta_exactos($org_id, 1, 1), get_resultados_pregunta_exactos($org_id, 1, 2)));?>'),
                        backgroundColor: [
                            'rgba(54, 162, 235, 0.2)',
                            'rgba(255, 99, 132, 0.2)',
                        ],
                        borderColor: [
                            'rgba(54, 162, 235, 0.2)',
                            'rgba(255, 99, 132, 0.2)',
                        ],
                        borderWidth: 1
                    }]
                }
            });

            var ctx_estado_civil = document.getElementById("estado_civil").getContext("2d");
            ctx_estado_civil.canvas.height = 400;
            ctx_estado_civil.canvas.width = document.getElementById('result-container').innerWidth;
            var grafica_estado_civil = new Chart(ctx_estado_civil, {
                type: 'pie',
                data: {
                    labels:  ['Con Pareja Estable', 'Sin Pareja Estable'],
                    datasets: [{
                        data: JSON.parse('<?php echo json_encode(array(get_resultados_pregunta_exactos($org_id, 3, 1), get_resultados_pregunta_exactos($org_id, 3, 2)));?>'),
                        backgroundColor: [
                            'rgba(255, 99, 132, 0.2)',
                            'rgba(54, 162, 235, 0.2)',
                        ],
                        borderColor: [
                            'rgba(255, 99, 132, 0.2)',
                            'rgba(54, 162, 235, 0.2)',
                        ],
                        borderWidth: 1
                    }]
                }
            });

            var ctx_tipo_contrato = document.getElementById("tipo_contrato").getContext("2d");
            ctx_tipo_contrato.canvas.height = 400;
            ctx_tipo_contrato.canvas.width = document.getElementById('result-container').innerWidth;
            var grafica_tipo_contrato = new Chart(ctx_tipo_contrato, {
                type: 'pie',
                data: {
                    labels:  [
                        'Contrato Por Tiempo Indefinido', 
                        'Contrato Por Tiempo Determinado', 
                        'Contrato A Prueba',
                        'Contrato Por Hora',
                        'Contrato De Capacitación Inicial',
                    ],
                    datasets: [{
                        data: JSON.parse('<?php 
                            echo json_encode(
                                array(
                                    get_resultados_pregunta_exactos($org_id, 6, 1), 
                                    get_resultados_pregunta_exactos($org_id, 6, 2),
                                    get_resultados_pregunta_exactos($org_id, 6, 3),
                                    get_resultados_pregunta_exactos($org_id, 6, 4),
                                    get_resultados_pregunta_exactos($org_id, 6, 5),
                                )
                            );
                            ?>'),
                        backgroundColor: [
                            'rgba(255, 99, 132, 0.2)',
                            'rgba(54, 162, 235, 0.2)',
                            'rgba(255, 206, 86, 0.2)',
                            'rgba(75, 192, 192, 0.2)',
                            'rgba(153, 102, 255, 0.2)'
                        ],
                        borderColor: [
                            'rgba(255, 99, 132, 0.2)',
                            'rgba(54, 162, 235, 0.2)',
                            'rgba(255, 206, 86, 0.2)',
                            'rgba(75, 192, 192, 0.2)',
                            'rgba(153, 102, 255, 0.2)'
                        ],
                        borderWidth: 1
                    }]
                }
            });

            var ctx_años_de_experiencia = document.getElementById("años_de_experiencia").getContext("2d");
            ctx_años_de_experiencia.canvas.height = 400;
            ctx_años_de_experiencia.canvas.width = document.getElementById('result-container').innerWidth;
            var grafica_años_de_experiencia = new Chart(ctx_años_de_experiencia, {
                type: 'bar',
                data: {
                    labels:  [
                        'Años Promedio En La Profesión', 
                        'Años Promedio En La Organización', 
                        'Años Promedio En El Puesto De Trabajo', 
                    ],
                    datasets: [{
                        label: 'Años',
                        data: JSON.parse('<?php 
                            echo json_encode(
                                array(
                                    get_promedio_pregunta($org_id, 7),
                                    get_promedio_pregunta($org_id, 8),
                                    get_promedio_pregunta($org_id, 9),
                                )
                            );
                            ?>'),
                        backgroundColor: [
                            'rgba(255, 99, 132, 0.2)',
                            'rgba(54, 162, 235, 0.2)',
                            'rgba(255, 206, 86, 0.2)',
                        ],
                        borderColor: [
                            'rgba(255, 99, 132, 0.2)',
                            'rgba(54, 162, 235, 0.2)',
                            'rgba(255, 206, 86, 0.2)',
                        ],
                        borderWidth: 1
                    }]
                }
            });

            var ctx_grados_estudio = document.getElementById("grados_estudio").getContext("2d");
            ctx_grados_estudio.canvas.height = 400;
            ctx_grados_estudio.canvas.width = document.getElementById('result-container').innerWidth;
            var grafica_grados_estudio = new Chart(ctx_grados_estudio, {
                type: 'pie',
                data: {
                    labels:  [
                        'Ninguno', 
                        'Primaria', 
                        'Secundaria', 
                        'Preparatoria', 
                        'Licenciatura', 
                        'Maestría', 
                        'Doctorado', 
                    ],
                    datasets: [{
                        data: JSON.parse('<?php 
                            echo json_encode(
                                array(
                                    get_resultados_pregunta_exactos($org_id, 10, 1), 
                                    get_resultados_pregunta_exactos($org_id, 10, 2),
                                    get_resultados_pregunta_exactos($org_id, 10, 3),
                                    get_resultados_pregunta_exactos($org_id, 10, 4),
                                    get_resultados_pregunta_exactos($org_id, 10, 5),
                                    get_resultados_pregunta_exactos($org_id, 10, 6),
                                    get_resultados_pregunta_exactos($org_id, 10, 7),
                                )
                            );
                            ?>'),
                        backgroundColor: [
                            'rgba(255, 99, 132, 0.2)',
                            'rgba(54, 162, 235, 0.2)',
                            'rgba(255, 206, 86, 0.2)',
                            'rgba(75, 192, 192, 0.2)',
                            'rgba(153, 102, 255, 0.2)',
                            'rgba(102, 154, 255, 0.2)',
                            'rgba(154, 255, 102, 0.2)',
                        ],
                        borderColor: [
                            'rgba(255, 99, 132, 0.2)',
                            'rgba(54, 162, 235, 0.2)',
                            'rgba(255, 206, 86, 0.2)',
                            'rgba(75, 192, 192, 0.2)',
                            'rgba(153, 102, 255, 0.2)',
                            'rgba(102, 154, 255, 0.2)',
                            'rgba(154, 255, 102, 0.2)',
                        ],
                        borderWidth: 1
                    }]
                }
            });
        </script>
        <?php
    } elseif ($grupo == 'ALCOHOLISMO_Y_TABAQUISMO') {
        ?>
        <div id="result-container" style="width: 100%;">
            <h2>Fumadores Y Bebedores</h2>
            <div>
                <canvas id="fumadores_bebedores" width="400" height="400"></canvas>
            </div>
            <h2>Fuman Al Día</h2>
            <div>
                <canvas id="cantidad_fumar" width="400" height="400"></canvas>
            </div>
            <h2>Beben Al Día</h2>
            <p><?php echo get_promedio_pregunta($org_id, 125);?> Unidades</p>
            
            <h2>¿Han Sentido Necesidad De Reducir Su Consumo De Alcohol?</h2>
            <p><?php echo get_promedio_pregunta($org_id, 126);?> Personas</p>

            <h2>Personas Que Durante Los Últimos 3 Meses Han Constatado Algún Cambio En Sus Hábitos De Consumo De Alcohol</h2>
            <div>
                <canvas id="personas_cambio_habitos" width="400" height="400"></canvas>
            </div>
        </div>

        <script>
            var ctx_fumadores_bebedores = document.getElementById("fumadores_bebedores").getContext("2d");
            ctx_fumadores_bebedores.canvas.height = 400;
            ctx_fumadores_bebedores.canvas.width = document.getElementById('result-container').innerWidth;
            var grafica_fumadores_bebedores = new Chart(ctx_fumadores_bebedores, {
                type: 'bar',
                data: {
                    labels:  ['Fumadores', 'Bebedores'],
                    datasets: [{
                        label: 'Personas',
                        data: JSON.parse('<?php echo json_encode(array(get_resultados_pregunta_exactos($org_id, 120, 1), get_resultados_pregunta_exactos($org_id, 124, 1)));?>'),
                        backgroundColor: [
                            'rgba(54, 162, 235, 0.2)',
                            'rgba(255, 99, 132, 0.2)',
                        ],
                        borderColor: [
                            'rgba(54, 162, 235, 0.2)',
                            'rgba(255, 99, 132, 0.2)',
                        ],
                        borderWidth: 1
                    }]
                }
            });

            var ctx_cantidad_fumar = document.getElementById("cantidad_fumar").getContext("2d");
            ctx_cantidad_fumar.canvas.height = 400;
            ctx_cantidad_fumar.canvas.width = document.getElementById('result-container').innerWidth;
            var grafica_cantidad_fumar = new Chart(ctx_cantidad_fumar, {
                type: 'bar',
                data: {
                    labels:  ['Cigarros', 'Puros', 'Pipas'],
                    datasets: [{
                        label: 'Al Día',
                        data: JSON.parse('<?php echo json_encode(array(
                                get_promedio_pregunta($org_id, 121),
                                get_promedio_pregunta($org_id, 122),
                                get_promedio_pregunta($org_id, 123),
                            ));?>'),
                        backgroundColor: [
                            'rgba(54, 162, 235, 0.2)',
                            'rgba(255, 99, 132, 0.2)',
                            'rgba(255, 206, 86, 0.2)',
                        ],
                        borderColor: [
                            'rgba(54, 162, 235, 0.2)',
                            'rgba(255, 99, 132, 0.2)',
                            'rgba(255, 206, 86, 0.2)',
                        ],
                        borderWidth: 1
                    }]
                }
            });

            var ctx_personas_cambio_habitos = document.getElementById("personas_cambio_habitos").getContext("2d");
            ctx_personas_cambio_habitos.canvas.height = 400;
            ctx_personas_cambio_habitos.canvas.width = document.getElementById('result-container').innerWidth;
            var grafica_personas_cambio_habitos = new Chart(ctx_personas_cambio_habitos, {
                type: 'bar',
                data: {
                    labels:  ['Consume Menos De Lo Habitual', 'Consume Igual Que Siempre', 'Consume Más De Lo Habitual'],
                    datasets: [{
                        label: 'Personas',
                        data: JSON.parse('<?php echo json_encode(array(
                                get_resultados_pregunta_exactos($org_id, 127, 0),
                                get_resultados_pregunta_exactos($org_id, 127, 1),
                                get_resultados_pregunta_exactos($org_id, 127, 2),
                            ));?>'),
                        backgroundColor: [
                            'rgba(54, 162, 235, 0.2)',
                            'rgba(255, 99, 132, 0.2)',
                            'rgba(255, 206, 86, 0.2)',
                        ],
                        borderColor: [
                            'rgba(54, 162, 235, 0.2)',
                            'rgba(255, 99, 132, 0.2)',
                            'rgba(255, 206, 86, 0.2)',
                        ],
                        borderWidth: 1
                    }]
                }
            });
        </script>
        <?php
    }
}

function get_resultados_pregunta_exactos($org_id, $pregunta, $respuesta) {
    global $wpdb;
    $table_resultados = $wpdb->prefix . "cesqt_resultados RS";
    $table_preguntas = $wpdb->prefix . "cesqt_preguntas P";
    $table_registros = $wpdb->prefix . "cesqt_registros R";

    $cantidad = (int)$wpdb->get_var(
        "SELECT COUNT(*) FROM $table_resultados, $table_preguntas, $table_registros
        WHERE RS.pregunta = P.id 
        AND RS.registro = R.id 
        AND R.organizacion = '$org_id'
        AND RS.pregunta = $pregunta
        AND RS.respuesta = $respuesta"
    );

    if ($cantidad > 0) {
        return $cantidad;
    } else {
        return 0;
    }
}

function get_promedio_pregunta($org_id, $pregunta) {
    global $wpdb;
    $table_resultados = $wpdb->prefix . "cesqt_resultados RS";
    $table_preguntas = $wpdb->prefix . "cesqt_preguntas P";
    $table_registros = $wpdb->prefix . "cesqt_registros R";

    $cantidad = (int)$wpdb->get_var(
        "SELECT COUNT(*) FROM $table_resultados, $table_preguntas, $table_registros
        WHERE RS.pregunta = P.id 
        AND RS.registro = R.id 
        AND R.organizacion = '$org_id'
        AND RS.pregunta = $pregunta"
    );
    
    $suma = (int)$wpdb->get_var(
        "SELECT SUM(RS.respuesta) FROM $table_resultados, $table_preguntas, $table_registros
        WHERE RS.pregunta = P.id 
        AND RS.registro = R.id 
        AND R.organizacion = '$org_id'
        AND RS.pregunta = $pregunta"
    );
    
    if ($cantidad > 0) {
        return $suma / $cantidad;
    } else {
        return 0;
    }
}


function construir_datos_grafica($grupo, $org_id) {
    global $wpdb;
    $table_grupos = $wpdb->prefix . "cesqt_grupos G";
    $table_preguntas = $wpdb->prefix . "cesqt_preguntas P";
    $table_registros = $wpdb->prefix . "cesqt_registros R";
    $table_resultados = $wpdb->prefix . "cesqt_resultados RS";

    $cantidad_preguntas = (int)$wpdb->get_var(
        "SELECT COUNT(*) FROM $table_preguntas, $table_grupos
        WHERE P.grupo = G.id
        AND G.nombre = '$grupo'"
    );

    $registros = $wpdb->get_results(
        "SELECT id FROM $table_registros WHERE organizacion = '$org_id'",
        'ARRAY_A'
    );

    $chart_data = array();
    switch($grupo) {
        case 'ILUSION_POR_EL_TRABAJO':
        case 'INDOLENCIA':
        case 'DESGASTE_PSIQUICO':
        case 'CULPA':
            $chart_data['labels'] = array('Nivel Alto', 'Nivel Bajo');
            $chart_data['data'] = array(0, 0);
            $chart_data['colors'] = array('rgba(255, 99, 132, 0.2)', 'rgba(54, 162, 235, 0.2)');
            foreach( $registros as $index => $row ){
                $sumatoria_respuestas_grupo_persona = (int)$wpdb->get_var(
                    "SELECT SUM(RS.respuesta) FROM 
                        $table_resultados, 
                        $table_preguntas, 
                        $table_registros, 
                        $table_grupos
                    WHERE RS.pregunta = P.id 
                    AND RS.registro = R.id 
                    AND P.grupo = G.id
                    AND G.nombre = '$grupo'
                    AND RS.registro = {$row['id']}"
                );
        
                $media = $sumatoria_respuestas_grupo_persona / $cantidad_preguntas;
        
                if ($media >= 2) {
                    $chart_data['data'][0]++;
                } else {
                    $chart_data['data'][1]++;
                }
            }
            break;
        default:
            // Niveles entre 0 y 1.5 son bajos, entre 1.6 y 2 son moderados y arriba de 2 alto
            $chart_data['labels'] = array('Nivel Alto', 'Nivel Moderado', 'Nivel Bajo');
            $chart_data['data'] = array(0, 0, 0);
            $chart_data['colors'] = array('rgba(255, 99, 132, 0.2)', 'rgba(54, 162, 235, 0.2)', 'rgba(255, 206, 86, 0.2)');
            foreach( $registros as $index => $row ){
                $sumatoria_respuestas_grupo_persona = (int)$wpdb->get_var(
                    "SELECT SUM(RS.respuesta) FROM 
                        $table_resultados, 
                        $table_preguntas, 
                        $table_registros, 
                        $table_grupos
                    WHERE RS.pregunta = P.id 
                    AND RS.registro = R.id 
                    AND P.grupo = G.id
                    AND G.nombre = '$grupo'
                    AND RS.registro = {$row['id']}"
                );
        
                $media = $sumatoria_respuestas_grupo_persona / $cantidad_preguntas;
        
                if ($media >= 2) {
                    $chart_data['data'][0]++;
                } elseif ($media >= 1.6 && $media < 2) {
                    $chart_data['data'][1]++;
                } else {
                    $chart_data['data'][2]++;
                }
            }
            
            break;
    }
    
    return $chart_data;
}
