<?php

namespace Database\Seeders;

use App\Models\Menu as ModelsMenu;
use App\Models\Role;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class Menu extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $developer = Role::findByName('developer');

        $menus = [
            [
                'icon' => 'LayoutDashboard',
                'label_name' => 'Dashboard',
                'controller_name' => 'app\Http\Controllers\DashboardController',
                'route_name' => 'dashboard',
                'url' => 'dashboard',
                'sort_num' => '1',
                'is_divider' => false
            ],
            [
                'icon' => 'BookKey',
                'label_name' => 'Access Settings',
                'controller_name' => null,
                'route_name' => 'rbac.index',
                'url' => 'rbac',
                'sort_num' => '2',
                'is_divider' => true
            ],
            [
                'icon' => 'SquareMenu',
                'label_name' => 'Navigation Management',
                'controller_name' => 'app\Http\Controllers\Rbac\NavigationManagementController',
                'route_name' => 'rbac.nav.index',
                'url' => 'rbac/navigation-management',
                'sort_num' => '3',
                'is_divider' => false
            ],
            [
                'icon' => 'KeyRound',
                'label_name' => 'Permission Management',
                'controller_name' => 'app\Http\Controllers\Rbac\PermissionManagementController',
                'route_name' => 'rbac.permission.index',
                'url' => 'rbac/permission-management',
                'sort_num' => '4',
                'is_divider' => false
            ],
            [
                'icon' => 'Shield',
                'label_name' => 'Role Management',
                'controller_name' => 'app\Http\Controllers\Rbac\RoleManagementController',
                'route_name' => 'rbac.role.index',
                'url' => 'rbac/role-management',
                'sort_num' => '5',
                'is_divider' => false
            ],
            [
                'icon' => 'User',
                'label_name' => 'User Management',
                'controller_name' => 'app\Http\Controllers\Rbac\UserManagementController',
                'route_name' => 'rbac.user.index',
                'url' => 'rbac/user-management',
                'sort_num' => '6',
                'is_divider' => false
            ],
            [
                'icon' => 'FolderKanban',
                'label_name' => 'RFID Management',
                'controller_name' => null,
                'route_name' => 'rfid-management.index',
                'url' => 'rfid-management',
                'sort_num' => '7',
                'is_divider' => true
            ],
            [
                'icon' => 'IdCardLanyard',
                'label_name' => 'Register New Card',
                'controller_name' => 'app\Http\Controllers\RfidManagement\RegisterNewCardController',
                'route_name' => 'rfid-management.register-new-card.index',
                'url' => 'rfid-management/register-new-card',
                'sort_num' => '8',
                'is_divider' => false
            ],
            [
                'icon' => 'IdCard',
                'label_name' => 'Card List',
                'controller_name' => 'app\Http\Controllers\RfidManagement\CardListController',
                'route_name' => 'rfid-management.card.index',
                'url' => 'rfid-management/card',
                'sort_num' => '9',
                'is_divider' => false
            ],
            [
                'icon' => 'Cpu',
                'label_name' => 'Devices',
                'controller_name' => 'app\Http\Controllers\RfidManagement\DevicesController',
                'route_name' => 'rfid-management.devices.index',
                'url' => 'rfid-management/devices',
                'sort_num' => '10',
                'is_divider' => false
            ],
            [
                'icon' => 'FileClock',
                'label_name' => 'Log Scan',
                'controller_name' => 'app\Http\Controllers\RfidManagement\LogScanController',
                'route_name' => 'rfid-management.log-scan.index',
                'url' => 'rfid-management/log-scan',
                'sort_num' => '11',
                'is_divider' => false
            ],
            [
                'icon' => 'Archive',
                'label_name' => 'Master Data',
                'controller_name' => null,
                'route_name' => 'master-data.index',
                'url' => 'master-data',
                'sort_num' => '12',
                'is_divider' => true
            ],
            [
                'icon' => 'CalendarClock',
                'label_name' => 'Work Time',
                'controller_name' => 'app\Http\Controllers\MasterData\WorkTimeController',
                'route_name' => 'master-data.work-time.index',
                'url' => 'master-data/work-time',
                'sort_num' => '13',
                'is_divider' => false
            ],
            [
                'icon' => 'CalendarCheck',
                'label_name' => 'Work Schedule',
                'controller_name' => 'app\Http\Controllers\MasterData\WorkScheduleController',
                'route_name' => 'master-data.work-schedule.index',
                'url' => 'master-data/work-schedule',
                'sort_num' => '14',
                'is_divider' => false
            ],
            [
                'icon' => 'NotebookPen',
                'label_name' => 'Schedule Assignment',
                'controller_name' => 'app\Http\Controllers\MasterData\ScheduleAssignmentController',
                'route_name' => 'master-data.schedule-assignment.index',
                'url' => 'master-data/schedule-assignment',
                'sort_num' => '15',
                'is_divider' => false
            ],
            [
                'icon' => 'CalendarX',
                'label_name' => 'Holidays',
                'controller_name' => 'app\Http\Controllers\MasterData\HolidayController',
                'route_name' => 'master-data.holiday.index',
                'url' => 'master-data/holiday',
                'sort_num' => '16',
                'is_divider' => false
            ],
            [
                'icon' => 'ClipboardPlus',
                'label_name' => 'Leave Types',
                'controller_name' => 'app\Http\Controllers\MasterData\LeaveTypeController',
                'route_name' => 'master-data.leave-type.index',
                'url' => 'master-data/leave-type',
                'sort_num' => '17',
                'is_divider' => false
            ],
            [
                'icon' => 'ClipboardCheck',
                'label_name' => 'Attendance Management',
                'controller_name' => null,
                'route_name' => 'attendance.index',
                'url' => 'attendance',
                'sort_num' => '18',
                'is_divider' => true
            ],
            [
                'icon' => 'BookOpenCheck',
                'label_name' => 'Attendance Data',
                'controller_name' => 'app\Http\Controllers\Attendance\AttendanceController',
                'route_name' => 'attendance.data.index',
                'url' => 'attendance/data',
                'sort_num' => '19',
                'is_divider' => false
            ],
            [
                'icon' => 'FilePenLine',
                'label_name' => 'Attendance Correction',
                'controller_name' => 'app\Http\Controllers\Attendance\AttendanceCorrectionController',
                'route_name' => 'attendance.correction.index',
                'url' => 'attendance/correction',
                'sort_num' => '20',
                'is_divider' => false
            ],
            [
                'icon' => 'Plane',
                'label_name' => 'Leave Management',
                'controller_name' => null,
                'route_name' => 'leave.index',
                'url' => 'leave',
                'sort_num' => '21',
                'is_divider' => true
            ],
            [
                'icon' => 'ClipboardSignature',
                'label_name' => 'Leave Request',
                'controller_name' => 'app\Http\Controllers\Leave\LeaveRequestController',
                'route_name' => 'leave.request.index',
                'url' => 'leave/request',
                'sort_num' => '22',
                'is_divider' => false
            ],
            [
                'icon' => 'Stamp',
                'label_name' => 'Leave Approval',
                'controller_name' => 'app\Http\Controllers\Leave\LeaveApprovalController',
                'route_name' => 'leave.approval.index',
                'url' => 'leave/approval',
                'sort_num' => '23',
                'is_divider' => false
            ],
        ];

        foreach ($menus as $menu) {
            $menuModel = ModelsMenu::updateOrCreate(
                [
                    'url' => $menu['url']
                ],
                [
                    'icon' => $menu['icon'],
                    'label_name' => $menu['label_name'],
                    'controller_name' => $menu['controller_name'],
                    'route_name' => $menu['route_name'],
                    'sort_num' => $menu['sort_num'],
                    'is_divider' => $menu['is_divider']
                ]
            );

            $developer->menus()->syncWithoutDetaching($menuModel->id);
        }
    }
}
