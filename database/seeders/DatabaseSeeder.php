<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Document;
use App\Models\User;
use App\Services\DocumentWorkflowService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        File::ensureDirectoryExists(public_path('files'));

        $departments = collect([
            ['name' => 'Финансы', 'code' => 'FIN'],
            ['name' => 'Кадры', 'code' => 'HR'],
            ['name' => 'Юридический отдел', 'code' => 'LAW'],
            ['name' => 'Закупки', 'code' => 'PROC'],
            ['name' => 'ИТ-служба', 'code' => 'IT'],
            ['name' => 'Продажи', 'code' => 'SALE'],
        ])->mapWithKeys(function (array $department) {
            $model = Category::create($department);

            return [$department['code'] => $model];
        });

        $admin = $this->makeUser([
            'name' => 'Алексей Орехов',
            'email' => 'admin@edo.local',
            'position' => 'Администратор системы',
            'phone' => '+7 900 000-00-01',
            'password' => 'Admin12345',
            'role' => User::ROLE_ADMIN,
        ], $departments->pluck('id')->all());

        $office = $this->makeUser([
            'name' => 'Марина Белова',
            'email' => 'office@edo.local',
            'position' => 'Специалист канцелярии',
            'phone' => '+7 900 000-00-02',
            'password' => 'Office12345',
            'role' => User::ROLE_OFFICE,
        ], $departments->pluck('id')->all());

        $financeHead = $this->makeUser([
            'name' => 'Ирина Новикова',
            'email' => 'fin.head@edo.local',
            'position' => 'Руководитель финансового отдела',
            'phone' => '+7 900 000-00-03',
            'password' => 'Workflow123',
            'role' => User::ROLE_DEPARTMENT_HEAD,
        ], [$departments['FIN']->id]);

        $hrHead = $this->makeUser([
            'name' => 'Татьяна Захарова',
            'email' => 'hr.head@edo.local',
            'position' => 'Руководитель отдела кадров',
            'phone' => '+7 900 000-00-04',
            'password' => 'Workflow123',
            'role' => User::ROLE_DEPARTMENT_HEAD,
        ], [$departments['HR']->id]);

        $itHead = $this->makeUser([
            'name' => 'Сергей Волков',
            'email' => 'it.head@edo.local',
            'position' => 'Руководитель ИТ-службы',
            'phone' => '+7 900 000-00-05',
            'password' => 'Workflow123',
            'role' => User::ROLE_DEPARTMENT_HEAD,
        ], [$departments['IT']->id]);

        $procurementHead = $this->makeUser([
            'name' => 'Ольга Романова',
            'email' => 'proc.head@edo.local',
            'position' => 'Руководитель отдела закупок',
            'phone' => '+7 900 000-00-06',
            'password' => 'Workflow123',
            'role' => User::ROLE_DEPARTMENT_HEAD,
        ], [$departments['PROC']->id]);

        $lawyer = $this->makeUser([
            'name' => 'Анна Власова',
            'email' => 'lawyer@edo.local',
            'position' => 'Юрисконсульт',
            'phone' => '+7 900 000-00-07',
            'password' => 'Employee123',
            'role' => User::ROLE_EMPLOYEE,
        ], [$departments['LAW']->id]);

        $accountant = $this->makeUser([
            'name' => 'Игорь Смирнов',
            'email' => 'accountant@edo.local',
            'position' => 'Главный бухгалтер',
            'phone' => '+7 900 000-00-08',
            'password' => 'Employee123',
            'role' => User::ROLE_EMPLOYEE,
        ], [$departments['FIN']->id]);

        $hrSpecialist = $this->makeUser([
            'name' => 'Елена Ковалева',
            'email' => 'hr@edo.local',
            'position' => 'HR-менеджер',
            'phone' => '+7 900 000-00-09',
            'password' => 'Employee123',
            'role' => User::ROLE_EMPLOYEE,
        ], [$departments['HR']->id]);

        $procurementManager = $this->makeUser([
            'name' => 'Максим Орлов',
            'email' => 'procurement@edo.local',
            'position' => 'Менеджер по закупкам',
            'phone' => '+7 900 000-00-10',
            'password' => 'Employee123',
            'role' => User::ROLE_EMPLOYEE,
        ], [$departments['PROC']->id]);

        $systemAnalyst = $this->makeUser([
            'name' => 'Денис Кузнецов',
            'email' => 'analyst@edo.local',
            'position' => 'Системный аналитик',
            'phone' => '+7 900 000-00-11',
            'password' => 'Employee123',
            'role' => User::ROLE_EMPLOYEE,
        ], [$departments['IT']->id]);

        /** @var DocumentWorkflowService $workflow */
        $workflow = app(DocumentWorkflowService::class);

        $approvedOrder = $this->createDocument([
            'name' => 'Приказ о ежегодной инвентаризации',
            'registration_number' => 'ORD-' . now()->format('Y') . '-0001',
            'document_type' => 'order',
            'priority' => 'high',
            'summary' => 'Проведение инвентаризации основных средств и товарных остатков по всем подразделениям.',
            'category_id' => $departments['FIN']->id,
            'visibility' => true,
            'due_at' => now()->addDays(5),
            'file' => $this->makeDemoFile('inventory-order.txt', 'Приказ о ежегодной инвентаризации'),
            'external_partner' => null,
        ], $accountant, $admin);

        $workflow->log($approvedOrder, $accountant, 'created', 'Документ создан', 'Финансовый отдел оформил проект приказа.');
        $workflow->submit($approvedOrder, $accountant);
        $workflow->approve($approvedOrder, $financeHead, 'Подтверждаю проведение инвентаризации по отделу.');
        $workflow->approve($approvedOrder, $admin, 'Маршрут согласован на уровне администрации.');
        $workflow->approve($approvedOrder, $office, 'Документ зарегистрирован в канцелярии.');

        $contract = $this->createDocument([
            'name' => 'Договор на обслуживание серверной инфраструктуры',
            'registration_number' => 'CTR-' . now()->format('Y') . '-0002',
            'document_type' => 'contract',
            'priority' => 'critical',
            'summary' => 'Продление сервисного контракта на хостинг, резервное копирование и мониторинг.',
            'category_id' => $departments['IT']->id,
            'visibility' => true,
            'due_at' => now()->addDays(2),
            'file' => $this->makeDemoFile('infra-contract.txt', 'Договор на обслуживание серверной инфраструктуры'),
            'external_partner' => 'ООО ТехноСервис',
        ], $systemAnalyst, $lawyer);

        $workflow->log($contract, $systemAnalyst, 'created', 'Документ создан', 'ИТ-служба оформила проект договора.');
        $workflow->submit($contract, $systemAnalyst);
        $workflow->approve($contract, $itHead, 'Технические условия проверены, договор можно передавать юристу.');

        $draftMemo = $this->createDocument([
            'name' => 'Служебная записка по найму аналитика продаж',
            'registration_number' => 'INT-' . now()->format('Y') . '-0003',
            'document_type' => 'internal',
            'priority' => 'normal',
            'summary' => 'Обоснование вакансии аналитика продаж и расчет фонда оплаты труда.',
            'category_id' => $departments['HR']->id,
            'visibility' => true,
            'due_at' => now()->addDays(4),
            'file' => $this->makeDemoFile('sales-analyst-hire.txt', 'Служебная записка по найму аналитика продаж'),
            'external_partner' => null,
        ], $hrSpecialist, $admin);

        $workflow->log($draftMemo, $hrSpecialist, 'created', 'Документ создан', 'Черновик готовится к отправке по маршруту.');

        $rejectedLetter = $this->createDocument([
            'name' => 'Исходящее письмо поставщику по пересмотру графика поставок',
            'registration_number' => 'OUT-' . now()->format('Y') . '-0004',
            'document_type' => 'outgoing',
            'priority' => 'high',
            'summary' => 'Предложение изменить график поставок с учетом сезонного пика продаж.',
            'category_id' => $departments['PROC']->id,
            'visibility' => true,
            'due_at' => now()->subDay(),
            'file' => $this->makeDemoFile('supplier-letter.txt', 'Исходящее письмо поставщику'),
            'external_partner' => 'АО СеверСнаб',
        ], $procurementManager, $lawyer);

        $workflow->log($rejectedLetter, $procurementManager, 'created', 'Документ создан', 'Закупки подготовили письмо поставщику.');
        $workflow->submit($rejectedLetter, $procurementManager);
        $workflow->approve($rejectedLetter, $procurementHead, 'Можно передавать в юридический блок.');
        $workflow->reject($rejectedLetter, $lawyer, 'Добавьте ссылку на приложение с новым графиком и уточните сроки действия условий.');

        $archivedAct = $this->createDocument([
            'name' => 'Входящий акт сверки взаиморасчетов',
            'registration_number' => 'IN-' . now()->format('Y') . '-0005',
            'document_type' => 'incoming',
            'priority' => 'normal',
            'summary' => 'Акт сверки за первый квартал от ключевого поставщика.',
            'category_id' => $departments['FIN']->id,
            'visibility' => true,
            'due_at' => now()->subDays(15),
            'file' => $this->makeDemoFile('reconciliation-act.txt', 'Входящий акт сверки взаиморасчетов'),
            'external_partner' => 'ООО Альянс Логистик',
        ], $accountant, $admin);

        $workflow->log($archivedAct, $accountant, 'created', 'Документ создан', 'Входящий акт зарегистрирован в финансовом отделе.');
        $workflow->submit($archivedAct, $accountant);
        $workflow->approve($archivedAct, $financeHead, 'Финансовые показатели подтверждены.');
        $workflow->approve($archivedAct, $admin, 'Документ согласован администрацией.');
        $workflow->approve($archivedAct, $office, 'Документ зарегистрирован и закрыт в канцелярии.');
        $workflow->archive($archivedAct, $office, 'Карточка передана в электронный архив после завершения маршрута.');
    }

    protected function makeUser(array $attributes, array $categoryIds): User
    {
        $user = User::create([
            ...$attributes,
            'email_verified_at' => now(),
        ]);

        $user->categories()->sync($categoryIds);

        return $user;
    }

    protected function createDocument(array $attributes, User $author, ?User $approver = null): Document
    {
        return Document::create([
            ...$attributes,
            'author_id' => $author->id,
            'approver_id' => $approver?->id,
            'status' => 'draft',
            'submitted_at' => null,
            'approved_at' => null,
            'archived_at' => null,
            'rejection_reason' => null,
            'workflow_round' => 0,
        ]);
    }

    /**
     * Create a demo text file in the public files folder.
     */
    protected function makeDemoFile(string $fileName, string $content): string
    {
        File::put(public_path('files/' . $fileName), $content);

        return $fileName;
    }
}
