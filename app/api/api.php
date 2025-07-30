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

        if (empty($date) || empty($service_id)) {
            echo json_encode(['success' => false, 'message' => 'Data ou ID do serviço não fornecidos.']);
            exit();
        }

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

        $start_hour = 9;  // 09:00
        $end_hour = 18; // 18:00
        $interval_minutes = 30; // Intervalo de slots

        $available_times = [];
        $current_timestamp = strtotime($date . ' ' . sprintf('%02d', $start_hour) . ':00:00');

        $booked_slots = [];
        $stmt_booked = $conn->prepare("SELECT hora_agendamento, s.duracao_minutos FROM agendamentos a JOIN servicos s ON a.servico_id = s.id WHERE a.data_agendamento = ? AND a.status != 'Cancelado'");
        if ($stmt_booked) {
            $stmt_booked->bind_param("s", $date);
            $stmt_booked->execute();
            $result_booked = $stmt_booked->get_result();
            while ($row = $result_booked->fetch_assoc()) {
                $booked_start_time = strtotime($date . ' ' . $row['hora_agendamento']);
                $booked_end_time = $booked_start_time + ($row['duracao_minutos'] * 60);
                $booked_slots[] = ['start' => $booked_start_time, 'end' => $booked_end_time];
            }
            $stmt_booked->close();
        }

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
        
        // Obter a data e hora atual (apenas o dia) para comparações
        $today = date('Y-m-d');
        $now_timestamp = time();

        while (date('H', $current_timestamp) < $end_hour) {
            $slot_start_time = $current_timestamp;
            $slot_end_time = $current_timestamp + ($service_duration * 60);

            // Se o slot proposto excede o horário de expediente ou já passou (para o dia de hoje)
            // Se o slot começar antes de $end_hour mas terminar depois, desconsiderar
            if (date('H', $slot_start_time) >= $end_hour || ($date == $today && $slot_end_time <= $now_timestamp)) {
                $current_timestamp += ($interval_minutes * 60); // Avança para o próximo slot base
                continue; // Pula este slot
            }
            
            // Garantir que o fim do slot não ultrapasse o final do expediente
            if (date('H', $slot_end_time) > $end_hour) {
                // Se o slot atual começa ANTES do final do expediente mas termina DEPOIS, não é um slot válido
                $current_timestamp += ($interval_minutes * 60); // Avança para o próximo slot base
                continue;
            }


            $is_available = true;

            // Verificar conflito com agendamentos existentes
            foreach ($booked_slots as $booked) {
                if (!($slot_start_time >= $booked['end'] || $slot_end_time <= $booked['start'])) {
                    $is_available = false;
                    break;
                }
            }

            // Verificar conflito com horários bloqueados
            if ($is_available) {
                foreach ($blocked_slots as $blocked) {
                    if (!($slot_start_time >= $blocked['end'] || $slot_end_time <= $blocked['start'])) {
                        $is_available = false;
                        break;
                    }
                }
            }
            
            // Verifica se o slot proposto para o serviço começa no futuro
            if ($date == $today && $slot_start_time < $now_timestamp) {
                $is_available = false; // Slot já passou
            }


            if ($is_available) {
                $available_times[] = date('H:i', $slot_start_time);
            }

            $current_timestamp += ($interval_minutes * 60);
        }

        echo json_encode($available_times);
        break;

    case 'make_appointment':
        // ############ Lógica para realizar um novo agendamento ############
        // Decodifica os dados JSON enviados na requisição POST
        $data = json_decode(file_get_contents('php://input'), true);

        // Sanitiza e valida todos os dados recebidos
        $nome = sanitize_input($data['nome'] ?? '');
        $email = sanitize_input($data['email'] ?? '');
        $telefone = sanitize_input($data['telefone'] ?? '');
        $servico_id = (int)($data['servico_id'] ?? 0);
        $data_agendamento = sanitize_input($data['data_agendamento'] ?? '');
        $hora_agendamento = sanitize_input($data['hora_agendamento'] ?? '');

        // Validação básica dos campos obrigatórios
        if (empty($nome) || empty($email) || empty($servico_id) || empty($data_agendamento) || empty($hora_agendamento)) {
            echo json_encode(['success' => false, 'message' => 'Por favor, preencha todos os campos obrigatórios.']);
            exit();
        }

        // Validação de email (formato)
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'message' => 'Formato de e-mail inválido.']);
            exit();
        }
        
        // Verificação adicional: O horário selecionado ainda está disponível?
        // É CRÍTICO fazer essa validação novamente aqui para evitar agendamentos duplos
        // se dois usuários tentarem agendar o mesmo slot quase simultaneamente.

        // 1. Obter a duração do serviço
        $service_duration_confirm = 0;
        $stmt_duration_confirm = $conn->prepare("SELECT duracao_minutos FROM servicos WHERE id = ?");
        if ($stmt_duration_confirm) {
            $stmt_duration_confirm->bind_param("i", $servico_id);
            $stmt_duration_confirm->execute();
            $stmt_duration_confirm->bind_result($duration_confirm);
            $stmt_duration_confirm->fetch();
            $service_duration_confirm = $duration_confirm;
            $stmt_duration_confirm->close();
        }

        if ($service_duration_confirm === 0) {
            echo json_encode(['success' => false, 'message' => 'Erro: Serviço não encontrado ou duração inválida para agendamento.']);
            exit();
        }

        $proposed_start_time = strtotime($data_agendamento . ' ' . $hora_agendamento);
        $proposed_end_time = $proposed_start_time + ($service_duration_confirm * 60);

        // Verificar se o horário proposto já passou (para agendamentos no dia de hoje)
        if ($data_agendamento == date('Y-m-d') && $proposed_start_time < time()) {
            echo json_encode(['success' => false, 'message' => 'O horário selecionado já passou. Por favor, escolha um horário futuro.']);
            exit();
        }


        // Re-verificar conflito com agendamentos existentes
        $stmt_check_booked = $conn->prepare("SELECT hora_agendamento, s.duracao_minutos FROM agendamentos a JOIN servicos s ON a.servico_id = s.id WHERE a.data_agendamento = ? AND a.status != 'Cancelado'");
        $is_available_final = true;
        if ($stmt_check_booked) {
            $stmt_check_booked->bind_param("s", $data_agendamento);
            $stmt_check_booked->execute();
            $result_check_booked = $stmt_check_booked->get_result();
            while ($row = $result_check_booked->fetch_assoc()) {
                $booked_start = strtotime($data_agendamento . ' ' . $row['hora_agendamento']);
                $booked_end = $booked_start + ($row['duracao_minutos'] * 60);
                
                if (!($proposed_start_time >= $booked_end || $proposed_end_time <= $booked_start)) {
                    $is_available_final = false;
                    break;
                }
            }
            $stmt_check_booked->close();
        }

        // Re-verificar conflito com horários bloqueados
        if ($is_available_final) {
            $stmt_check_blocked = $conn->prepare("SELECT hora_inicio, hora_fim FROM horarios_bloqueados WHERE data = ?");
            if ($stmt_check_blocked) {
                $stmt_check_blocked->bind_param("s", $data_agendamento);
                $stmt_check_blocked->execute();
                $result_check_blocked = $stmt_check_blocked->get_result();
                while ($row = $result_check_blocked->fetch_assoc()) {
                    $block_start = strtotime($data_agendamento . ' ' . $row['hora_inicio']);
                    $block_end = strtotime($data_agendamento . ' ' . $row['hora_fim']);
                    
                    if (!($proposed_start_time >= $block_end || $proposed_end_time <= $block_start)) {
                        $is_available_final = false;
                        break;
                    }
                }
                $stmt_check_blocked->close();
            }
        }

        if (!$is_available_final) {
            echo json_encode(['success' => false, 'message' => 'O horário selecionado não está mais disponível. Por favor, escolha outro.']);
            exit();
        }

        // Se todas as validações passarem, insere o agendamento no banco de dados
        $stmt_insert = $conn->prepare("INSERT INTO agendamentos (cliente_nome, cliente_email, cliente_telefone, servico_id, data_agendamento, hora_agendamento, status) VALUES (?, ?, ?, ?, ?, ?, 'Pendente')");
        
        // Verifica se a preparação da query falhou
        if (!$stmt_insert) {
            echo json_encode(['success' => false, 'message' => 'Erro interno do servidor ao preparar agendamento: ' . $conn->error]);
            exit();
        }

        $stmt_insert->bind_param("sssiss", $nome, $email, $telefone, $servico_id, $data_agendamento, $hora_agendamento);

        if ($stmt_insert->execute()) {
            echo json_encode(['success' => true, 'message' => 'Agendamento realizado com sucesso! Em breve entraremos em contato para confirmação.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erro ao agendar: ' . $stmt_insert->error]);
        }
        $stmt_insert->close();
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Ação inválida ou não especificada.']);
        break;
}

$conn->close();