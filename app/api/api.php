<?php
// app/api/api.php

header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';

$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Falha na conexão com o banco de dados: ' . $conn->connect_error]);
    exit();
}

$conn->set_charset(DB_CHARSET);

function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

$action = isset($_GET['action']) ? sanitize_input($_GET['action']) : '';

switch ($action) {
    case 'get_services':
        // ############ Lógica para obter a lista de serviços ############
        $sql = "SELECT id, nome, duracao_minutos, preco FROM servicos ORDER BY nome ASC";
        $result = $conn->query($sql);

        $services = [];

        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $services[] = $row;
            }
        }
        echo json_encode($services);
        break;

    case 'get_available_times':
        // ############ Lógica para obter horários disponíveis ############
        $date = isset($_GET['date']) ? sanitize_input($_GET['date']) : '';
        $service_id = isset($_GET['service_id']) ? (int)$_GET['service_id'] : 0;

        // Validação básica dos parâmetros
        if (empty($date) || empty($service_id)) {
            echo json_encode(['success' => false, 'message' => 'Data ou ID do serviço não fornecidos.']);
            exit();
        }

        // 1. Obter a duração do serviço
        $service_duration = 0;
        $stmt_duration = $conn->prepare("SELECT duracao_minutos FROM servicos WHERE id = ?");
        if ($stmt_duration) {
            $stmt_duration->bind_param("i", $service_id);
            $stmt_duration->execute();
            $stmt_duration->bind_result($duration);
            $stmt_duration->fetch();
            $service_duration = $duration;
            $stmt_duration->close();
        }

        if ($service_duration === 0) {
            echo json_encode(['success' => false, 'message' => 'Serviço não encontrado ou duração inválida.']);
            exit();
        }

        // Definir os horários de trabalho do cabeleireiro (ex: 9h às 18h, com intervalos de 30 minutos)
        // Isso pode ser puxado da tabela 'disponibilidade' no futuro para maior flexibilidade
        $start_hour = 9;  // 09:00
        $end_hour = 18; // 18:00
        $interval_minutes = 30; // Intervalo de slots

        $available_times = [];
        $current_timestamp = strtotime($date . ' ' . sprintf('%02d', $start_hour) . ':00:00'); // Timestamp do início do dia de trabalho

        // 2. Obter agendamentos existentes para a data
        $booked_slots = [];
        $stmt_booked = $conn->prepare("SELECT hora_agendamento, s.duracao_minutos FROM agendamentos a JOIN servicos s ON a.servico_id = s.id WHERE a.data_agendamento = ? AND a.status != 'Cancelado'");
        if ($stmt_booked) {
            $stmt_booked->bind_param("s", $date);
            $stmt_booked->execute();
            $result_booked = $stmt_booked->get_result();
            while ($row = $result_booked->fetch_assoc()) {
                $booked_start_time = strtotime($date . ' ' . $row['hora_agendamento']);
                $booked_end_time = $booked_start_time + ($row['duracao_minutos'] * 60); // Agendamento termina X minutos depois
                $booked_slots[] = ['start' => $booked_start_time, 'end' => $booked_end_time];
            }
            $stmt_booked->close();
        }

        // 3. Obter horários bloqueados (feriados, folgas, etc.)
        $blocked_slots = [];
        $stmt_blocked = $conn->prepare("SELECT hora_inicio, hora_fim FROM horarios_bloqueados WHERE data = ?");
        if ($stmt_blocked) {
            $stmt_blocked->bind_param("s", $date);
            $stmt_blocked->execute();
            $result_blocked = $stmt_blocked->get_result();
            while ($row = $result_blocked->fetch_assoc()) {
                $block_start = strtotime($date . ' ' . $row['hora_inicio']);
                $block_end = strtotime($date . ' ' . $row['hora_fim']);
                $blocked_slots[] = ['start' => $block_start, 'end' => $block_end];
            }
            $stmt_blocked->close();
        }

        // 4. Gerar slots de tempo e verificar disponibilidade
        while (date('H', $current_timestamp) < $end_hour) {
            $slot_start_time = $current_timestamp;
            $slot_end_time = $current_timestamp + ($service_duration * 60);

            // Se o slot terminar depois do horário de expediente, ou se já passou do horário atual (para o dia de hoje)
            if (date('H', $slot_end_time) > $end_hour || ($date == date('Y-m-d') && $slot_end_time <= time())) {
                // Se o slot começar antes de $end_hour mas terminar depois, desconsiderar
                if (date('H', $slot_start_time) < $end_hour) {
                     // Adiciona o intervalo de minutos para ir para o próximo slot
                    $current_timestamp += ($interval_minutes * 60);
                    continue; // Pula para a próxima iteração
                }
                break; // Se o início já passou do final do expediente, para o loop
            }

            $is_available = true;

            // Verificar conflito com agendamentos existentes
            foreach ($booked_slots as $booked) {
                // Se o slot proposto se sobrepõe a um agendamento existente
                if (!($slot_start_time >= $booked['end'] || $slot_end_time <= $booked['start'])) {
                    $is_available = false;
                    break;
                }
            }

            // Verificar conflito com horários bloqueados
            if ($is_available) { // Só verifica se ainda está disponível
                foreach ($blocked_slots as $blocked) {
                    // Se o slot proposto se sobrepõe a um horário bloqueado
                    if (!($slot_start_time >= $blocked['end'] || $slot_end_time <= $blocked['start'])) {
                        $is_available = false;
                        break;
                    }
                }
            }

            if ($is_available) {
                $available_times[] = date('H:i', $slot_start_time);
            }

            // Move para o próximo slot de tempo
            $current_timestamp += ($interval_minutes * 60);
        }

        echo json_encode($available_times);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Ação inválida ou não especificada.']);
        break;
}

$conn->close();