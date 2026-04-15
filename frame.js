// Navigation
const navItems = document.querySelectorAll('.nav-item');
const pageSections = document.querySelectorAll('section[data-page]');

navItems.forEach(item => {
    item.addEventListener('click', function(e) {
        e.preventDefault();
        navItems.forEach(i => i.classList.remove('active'));
        this.classList.add('active');

        const page = this.dataset.page;
        pageSections.forEach(section => {
            section.classList.toggle('hidden', section.dataset.page !== page);
        });
    });
});

// Task checkboxes
document.querySelectorAll('.task-item input').forEach(checkbox => {
    checkbox.addEventListener('change', function() {
        const task = this.closest('.task-item');
        task.style.opacity = this.checked ? '0.5' : '1';
        task.querySelector('h4').style.textDecoration = this.checked ? 'line-through' : 'none';
    });
});
