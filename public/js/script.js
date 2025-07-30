// Aguarda o carregamento completo do DOM antes de executar o script
document.addEventListener('DOMContentLoaded', () => {
    // ############ Seleção de Elementos HTML ############
    // Obtém referências para os elementos do formulário usando seus IDs
    const servicoSelect = document.getElementById('servico');
    const dataInput = document.getElementById('data');
    const horaSelect = document.getElementById('hora');
    const appointmentForm = document.getElementById('appointmentForm');
    const messageDiv = document.getElementById('message');

    // ############ Funções Assíncronas para Interação com a API (PHP) ############
    // Função para carregar os serviços disponíveis do back-end
    async function loadServices() {
        try {
            // Faz uma requisição GET para a API para obter a lista de serviços
            const response = await fetch('../app/api/api.php?action=get_services');
            // Converte a resposta para JSON
            const services = await response.json();

            // Limpa as opções existentes no seletor de serviços
            servicoSelect.innerHTML = '<option value="">Selecione um serviço</option>';

            // Adiciona cada serviço retornado como uma nova opção no seletor
            services.forEach(service => {
                const option = document.createElement('option');
                option.value = service.id;
                // Formata o texto da opção com nome, duração e preço
                option.textContent = `${service.nome} (Duração: ${service.duracao_minutos} min, Preço: R$ ${parseFloat(service.preco).toFixed(2)})`;
                servicoSelect.appendChild(option);
            });
        } catch (error) {
            // Em caso de erro na requisição, loga o erro e exibe uma mensagem no seletor
            console.error('Erro ao carregar serviços:', error);
            servicoSelect.innerHTML = '<option value="">Erro ao carregar serviços</option>';
        }
    }

     // Função para carregar os horários disponíveis com base na data e serviço selecionados
    async function loadAvailableTimes() {
        const selectedDate = dataInput.value; // Obtém a data selecionada pelo usuário
        const selectedServiceId = servicoSelect.value; // Obtém o ID do serviço selecionado

        // Limpa o seletor de horários e o desabilita enquanto carrega ou se as condições não forem atendidas
        horaSelect.innerHTML = '<option value="">Carregando horários...</option>';
        horaSelect.disabled = true;

        // Verifica se uma data e um serviço foram selecionados antes de buscar horários
        if (!selectedDate || !selectedServiceId) {
            horaSelect.innerHTML = '<option value="">Selecione uma data e um serviço primeiro</option>';
            return; // Sai da função se as condições não forem atendidas
        }

        try {
            // Faz uma requisição GET para a API para obter os horários disponíveis
            // Passa a data e o ID do serviço como parâmetros na URL
            const response = await fetch(`../app/api/api.php?action=get_available_times&date=${selectedDate}&service_id=${selectedServiceId}`);
            const times = await response.json(); // Converte a resposta para JSON (uma array de horários)

            // Limpa o seletor de horários
            horaSelect.innerHTML = '<option value="">Selecione um horário</option>';

            // Se houver horários disponíveis, popula o seletor
            if (times.length > 0) {
                times.forEach(time => {
                    const option = document.createElement('option');
                    option.value = time;
                    option.textContent = time;
                    horaSelect.appendChild(option);
                });
                horaSelect.disabled = false; // Habilita o seletor de horários
            } else {
                // Se não houver horários, exibe uma mensagem no seletor
                horaSelect.innerHTML = '<option value="">Nenhum horário disponível para esta data e serviço.</option>';
            }
        } catch (error) {
            // Em caso de erro na requisição, loga e exibe uma mensagem
            console.error('Erro ao carregar horários:', error);
            horaSelect.innerHTML = '<option value="">Erro ao carregar horários</option>';
        }
    }

    // ############ Event Listeners ############
    // Adiciona um "ouvinte de evento" para quando o serviço selecionado muda
    servicoSelect.addEventListener('change', loadAvailableTimes);
    // Adiciona um "ouvinte de evento" para quando a data selecionada muda
    dataInput.addEventListener('change', loadAvailableTimes);

    // Adiciona um "ouvinte de evento" para o envio do formulário
    appointmentForm.addEventListener('submit', async (e) => {
        // Previne o comportamento padrão de recarregar a página ao submeter o formulário
        e.preventDefault();

        // Coleta os dados do formulário em um objeto
        const formData = {
            nome: document.getElementById('nome').value,
            email: document.getElementById('email').value,
            telefone: document.getElementById('telefone').value,
            servico_id: document.getElementById('servico').value,
            data_agendamento: document.getElementById('data').value,
            hora_agendamento: document.getElementById('hora').value
        };

        try {
            // Faz uma requisição POST para a API para realizar o agendamento
            const response = await fetch('api.php?action=make_appointment', {
                method: 'POST', // Define o método como POST
                headers: {
                    'Content-Type': 'application/json' // Informa ao servidor que o corpo da requisição é JSON
                },
                body: JSON.stringify(formData) // Converte o objeto formData para uma string JSON
            });
            const result = await response.json(); // Converte a resposta do servidor para JSON

            // Verifica se o agendamento foi bem-sucedido com base na resposta da API
            if (result.success) {
                messageDiv.className = 'mt-3 alert alert-success'; // Adiciona classes CSS para sucesso
                messageDiv.textContent = result.message || 'Agendamento realizado com sucesso! Em breve entraremos em contato para confirmação.';
                appointmentForm.reset(); // Limpa todos os campos do formulário
                horaSelect.innerHTML = '<option value="">Selecione uma data primeiro</option>'; // Reseta o seletor de horas
                horaSelect.disabled = true; // Desabilita o seletor de horas
                loadServices(); // Recarrega os serviços (útil se o estado dos serviços puder mudar)
            } else {
                messageDiv.className = 'mt-3 alert alert-danger'; // Adiciona classes CSS para erro
                messageDiv.textContent = result.message || 'Erro ao realizar agendamento.';
            }
        } catch (error) {
            // Em caso de erro na requisição (ex: problema de rede), loga e exibe mensagem de erro genérica
            console.error('Erro na requisição de agendamento:', error);
            messageDiv.className = 'mt-3 alert alert-danger';
            messageDiv.textContent = 'Erro de conexão ou servidor. Por favor, tente novamente.';
        }
    });

    // ############ Chamadas de Funções Iniciais ############
    // Chama a função para carregar os serviços assim que a página é carregada
    loadServices();
});