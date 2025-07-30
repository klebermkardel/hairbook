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
            const response = await fetch('api.php?action=get_services');
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

    // ############ Chamadas de Funções Iniciais ############
    // Chama a função para carregar os serviços assim que a página é carregada
    loadServices();

    // A próxima parte vai lidar com o carregamento de horários disponíveis e o envio do formulário.
});