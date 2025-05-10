document.addEventListener('DOMContentLoaded', function () {
    const tabs = document.querySelectorAll('.tab');
    const slider = document.querySelector('.tab-slider');
    const loginForm = document.querySelector('.form-login');
    const registerForm = document.querySelector('.form-register');
    const forms = document.querySelectorAll('.login-form');
    const tabsContainer = document.querySelector('.login-tabs');
    const allInputs = document.querySelectorAll('.login-input');
    const loginFormElement = document.getElementById('login-form');
    const registerFormElement = document.getElementById('register-form');
    const tabContent = document.querySelector('.tab-content');
    
    function setActiveTab(tabName) {
        document.querySelector('.tab.active').classList.remove('active');
        document.querySelector('.tab[data-tab="' + tabName + '"]').classList.add('active');
        
        document.querySelector('.login-form.active').classList.remove('active');
        if (tabName === 'login') {
            loginForm.classList.add('active');
        } else {
            registerForm.classList.add('active');
        }

        const containerWidth = tabsContainer.offsetWidth;
        const tabWidth = containerWidth / 2;
        const padding = 6;
        const sliderWidth = tabWidth - (padding * 2);
        
        slider.style.width = sliderWidth + 'px';
        slider.style.left = (tabName === 'login' ? padding : (tabWidth + padding)) + 'px';
    }

    loginFormElement.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        formData.append('action', 'login');
        
        try {
            const response = await fetch('index.php', {
                method: 'POST',
                body: formData
            });
            
            if (!response.ok) {
                throw new Error('Ошибка сети');
            }
            
            const data = await response.json();
            
            if (data.success) {
                showMessage(data.message, 'success');
                setTimeout(() => window.location.href = 'dashboard.php', 1000);
            } else {
                showMessage(data.message, 'error');
            }
        } catch (error) {
            console.error('Ошибка запроса:', error);
            showMessage('Произошла ошибка при отправке запроса', 'error');
        }
    });

    registerFormElement.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        formData.append('action', 'register');
        
        try {
            const response = await fetch('index.php', {
                method: 'POST',
                body: formData
            });
            
            if (!response.ok) {
                throw new Error('Ошибка сети');
            }
            
            const data = await response.json();
            showMessage(data.message, data.success ? 'success' : 'error');
            
            if (data.success) {
                this.reset();
                setTimeout(() => setActiveTab('login'), 1500);
            }
        } catch (error) {
            console.error('Ошибка запроса:', error);
            showMessage('Произошла ошибка при отправке запроса', 'error');
        }
    });

    function showMessage(message, type) {
        const existingMessage = document.querySelector('.error-message, .success-message');
        if (existingMessage) {
            existingMessage.remove();
        }

        const messageElement = document.createElement('div');
        messageElement.className = type + '-message';
        messageElement.textContent = message;
        tabContent.appendChild(messageElement);

        setTimeout(() => {
            messageElement.style.opacity = '0';
            setTimeout(() => messageElement.remove(), 300);
        }, 3000);
    }

    tabs.forEach(tab => {
        tab.addEventListener('click', function() {
            const tabName = this.getAttribute('data-tab');
            setActiveTab(tabName);
        });
    });

    allInputs.forEach(input => {
        input.addEventListener('focus', function() {
            this.closest('.login-form').classList.add('form-focused');
        });
        
        input.addEventListener('blur', function() {
            this.closest('.login-form').classList.remove('form-focused');
        });
    });

    setActiveTab('login');

    const container = document.querySelector('.login-container');
    container.style.opacity = '0';
    
    requestAnimationFrame(() => {
        container.style.transition = 'opacity 0.8s cubic-bezier(.4,0,.2,1)';
        container.style.opacity = '1';
    });

    let resizeTimeout;
    window.addEventListener('resize', () => {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(() => {
            const activeTab = document.querySelector('.tab.active').getAttribute('data-tab');
            setActiveTab(activeTab);
        }, 100);
    });
});