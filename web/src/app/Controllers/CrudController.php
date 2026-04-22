<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\EntityRepository;

final class CrudController extends BaseController
{
    private EntityRepository $repository;

    public function __construct()
    {
        $this->repository = new EntityRepository();
    }

    public function index(string $entity): void
    {
        $config = $this->repository->config($entity);
        $this->render('crud/list', [
            'title' => $config['title'],
            'entity' => $entity,
            'config' => $config,
            'rows' => $this->repository->all($entity),
        ]);
    }

    public function create(string $entity): void
    {
        $config = $this->repository->config($entity);
        $this->render('crud/form', [
            'title' => 'Создать: ' . $config['singular'],
            'entity' => $entity,
            'config' => $config,
            'item' => [],
            'errors' => [],
            'options' => $this->fieldOptions($config),
            'action' => '/' . $entity,
        ]);
    }

    public function store(string $entity): void
    {
        if (!$this->requirePostWithCsrf()) {
            return;
        }

        $config = $this->repository->config($entity);
        $errors = $this->repository->validate($entity, $_POST);

        if ($errors !== []) {
            $_SESSION['old'] = $_POST;
            $this->render('crud/form', [
                'title' => 'Создать: ' . $config['singular'],
                'entity' => $entity,
                'config' => $config,
                'item' => [],
                'errors' => $errors,
                'options' => $this->fieldOptions($config),
                'action' => '/' . $entity,
            ], 422);
            return;
        }

        $this->repository->create($entity, $_POST, (int) current_user()['id']);
        flash('success', 'Запись создана.');
        $this->redirect('/' . $entity);
    }

    public function edit(string $entity, string $id): void
    {
        $config = $this->repository->config($entity);
        $item = $this->repository->find($entity, (int) $id);

        if (!$item) {
            http_response_code(404);
            echo 'Запись не найдена.';
            return;
        }

        $this->render('crud/form', [
            'title' => 'Редактировать: ' . $config['singular'],
            'entity' => $entity,
            'config' => $config,
            'item' => $item,
            'errors' => [],
            'options' => $this->fieldOptions($config),
            'action' => '/' . $entity . '/' . (int) $id,
        ]);
    }

    public function update(string $entity, string $id): void
    {
        if (!$this->requirePostWithCsrf()) {
            return;
        }

        $config = $this->repository->config($entity);
        $item = $this->repository->find($entity, (int) $id);
        if (!$item) {
            http_response_code(404);
            echo 'Запись не найдена.';
            return;
        }

        $errors = $this->repository->validate($entity, $_POST);
        if ($errors !== []) {
            $_SESSION['old'] = $_POST;
            $this->render('crud/form', [
                'title' => 'Редактировать: ' . $config['singular'],
                'entity' => $entity,
                'config' => $config,
                'item' => $item,
                'errors' => $errors,
                'options' => $this->fieldOptions($config),
                'action' => '/' . $entity . '/' . (int) $id,
            ], 422);
            return;
        }

        $this->repository->update($entity, (int) $id, $_POST);
        flash('success', 'Запись обновлена.');
        $this->redirect('/' . $entity);
    }

    public function delete(string $entity, string $id): void
    {
        if (!$this->requirePostWithCsrf()) {
            return;
        }

        if (!has_role('admin')) {
            http_response_code(403);
            echo 'Удаление доступно только администратору.';
            return;
        }

        $this->repository->delete($entity, (int) $id);
        flash('success', 'Запись удалена.');
        $this->redirect('/' . $entity);
    }

    private function fieldOptions(array $config): array
    {
        $options = [];
        foreach ($config['fields'] as $name => $field) {
            if (!isset($field['source'])) {
                continue;
            }
            $options[$name] = $this->repository->options($field['source']);
        }

        return $options;
    }
}
