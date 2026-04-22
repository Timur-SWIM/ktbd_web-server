<div class="page-heading">
    <div>
        <h1>Панель управления</h1>
        <p>Основные показатели производственного участка</p>
    </div>
</div>

<div class="metric-grid">
    <section class="metric-card">
        <span>Изделия</span>
        <strong><?= e((string) $metrics['devices']) ?></strong>
        <small>Всего в системе</small>
    </section>
    <section class="metric-card">
        <span>Сотрудники</span>
        <strong><?= e((string) $metrics['staff']) ?></strong>
        <small>Активный учет персонала</small>
    </section>
    <section class="metric-card">
        <span>Документы</span>
        <strong><?= e((string) $metrics['documents']) ?></strong>
        <small>Технологическая документация</small>
    </section>
    <section class="metric-card">
        <span>Оборудование доступно</span>
        <strong><?= e((string) $metrics['tools_available']) ?></strong>
        <small>Готово к использованию</small>
    </section>
    <section class="metric-card">
        <span>Оборудование в работе</span>
        <strong><?= e((string) $metrics['tools_in_use']) ?></strong>
        <small>Используется на участке</small>
    </section>
</div>
