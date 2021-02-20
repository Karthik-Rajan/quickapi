<?php

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
    	Schema::disableForeignKeyConstraints();

    	DB::table('model_has_roles')->truncate();

        DB::table('permissions')->truncate();

        $admin = Role::where('name', 'ADMIN')->first();

    	DB::table('permissions')->insert([
            ['name' => 'dashboard-menus', 'display_name' => 'Box Menus', 'guard_name' => 'admin', 'group_name' => 'Dashboard'],
            ['name' => 'wallet-summary', 'display_name' => 'Wallet Summary', 'guard_name' => 'admin', 'group_name' => 'Dashboard'],
            
            ['name' => 'graph by-country', 'display_name' => 'Graph by Country', 'guard_name' => 'admin', 'group_name' => 'Dashboard'],
            ['name' => 'no-of-user', 'display_name' => 'No Of Patient', 'guard_name' => 'admin', 'group_name' => 'Dashboard'],
            ['name' => 'no-of-provider', 'display_name' => 'No Of CCM', 'guard_name' => 'admin', 'group_name' => 'Dashboard'],
            ['name' => 'no-of-shop', 'display_name' => 'No Of Pharmacy', 'guard_name' => 'admin', 'group_name' => 'Dashboard'],


            ['name' => 'dispatcher-panel', 'display_name' => 'Dispatcher Menu', 'guard_name' => 'admin', 'group_name' => 'Dispatcher Panel'],
            ['name' => 'dispatcher-panel-add', 'display_name' => 'Add Rides', 'guard_name' => 'admin', 'group_name' => 'Dispatcher Panel'],

            ['name' => 'dispute-list', 'display_name' => 'Dispute list', 'guard_name' => 'admin', 'group_name' => 'Dispute'],
            ['name' => 'dispute-create', 'display_name' => 'Create Dispute', 'guard_name' => 'admin', 'group_name' => 'Dispute'],
            ['name' => 'dispute-edit', 'display_name' => 'Edit Dispute', 'guard_name' => 'admin', 'group_name' => 'Dispute'],
            ['name' => 'dispute-delete', 'display_name' => 'Delete Dispute', 'guard_name' => 'admin', 'group_name' => 'Dispute'],
            ['name' => 'dispute-status', 'display_name' => 'Status Dispute', 'guard_name' => 'admin', 'group_name' => 'Dispute'],

            // SERVICE DISPUTE PERMISSION SET
            ['name' => 'service-dispute-list', 'display_name' => 'Service Disputes list', 'guard_name' => 'admin', 'group_name' => 'Service Disputes'],
            ['name' => 'service-dispute-create', 'display_name' => 'Create Service Disputes', 'guard_name' => 'admin', 'group_name' => 'Service Disputes'],
            ['name' => 'service-dispute-edit', 'display_name' => 'Edit Service Disputes', 'guard_name' => 'admin', 'group_name' => 'Service Disputes'],
            ['name' => 'service-dispute-delete', 'display_name' => 'Delete Service Disputes', 'guard_name' => 'admin', 'group_name' => 'Service Disputes'],
            ['name' => 'service-dispute-status', 'display_name' => 'Status Service Disputes', 'guard_name' => 'admin', 'group_name' => 'Service Disputes'],

            // Order  DISPUTE PERMISSION SET
            ['name' => 'order-dispute-list', 'display_name' => 'Order Disputes list', 'guard_name' => 'admin', 'group_name' => 'Order Disputes'],
            ['name' => 'order-dispute-create', 'display_name' => 'Create Order Disputes', 'guard_name' => 'admin', 'group_name' => 'Order Disputes'],
            ['name' => 'order-dispute-edit', 'display_name' => 'Edit Order Disputes', 'guard_name' => 'admin', 'group_name' => 'Order Disputes'],
            ['name' => 'order-dispute-delete', 'display_name' => 'Delete Order Disputes', 'guard_name' => 'admin', 'group_name' => 'Order Disputes'],
            ['name' => 'order-dispute-status', 'display_name' => 'Status Order Disputes', 'guard_name' => 'admin', 'group_name' => 'Order Disputes'],


            ['name' => 'heat-map', 'display_name' => 'Heat Map', 'guard_name' => 'admin', 'group_name' => 'Map'],
            ['name' => 'god-eye', 'display_name' => 'God\'s Eye', 'guard_name' => 'admin', 'group_name' => 'Map'],

            ['name' => 'user-list', 'display_name' => 'Patient list', 'guard_name' => 'admin', 'group_name' => 'Patient'],
            ['name' => 'user-history', 'display_name' => 'Patient History', 'guard_name' => 'admin', 'group_name' => 'Patient'],
            ['name' => 'user-create', 'display_name' => 'Create Patient', 'guard_name' => 'admin', 'group_name' => 'Patient'],
            ['name' => 'user-edit', 'display_name' => 'Edit Patient', 'guard_name' => 'admin', 'group_name' => 'Patient'],
            ['name' => 'user-delete', 'display_name' => 'Delete Patient', 'guard_name' => 'admin', 'group_name' => 'Patient'],
            ['name' => 'user-status', 'display_name' => 'Status Patient', 'guard_name' => 'admin', 'group_name' => 'Patient'],

            ['name' => 'provider-list', 'display_name' => 'CCM list', 'guard_name' => 'admin', 'group_name' => 'CCM'],
            ['name' => 'provider-create', 'display_name' => 'Create CCM', 'guard_name' => 'admin', 'group_name' => 'CCM'],
            ['name' => 'provider-edit', 'display_name' => 'Edit CCM', 'guard_name' => 'admin', 'group_name' => 'CCM'],
            ['name' => 'provider-delete', 'display_name' => 'Delete CCM', 'guard_name' => 'admin', 'group_name' => 'CCM'],
            ['name' => 'provider-status', 'display_name' => 'CCM Status', 'guard_name' => 'admin', 'group_name' => 'CCM'],
            ['name' => 'status-provider', 'display_name' => 'Status CCM ', 'guard_name' => 'admin', 'group_name' => 'CCM'],
            ['name' => 'provider-history', 'display_name' => 'Ride History', 'guard_name' => 'admin', 'group_name' => 'CCM'],
            ['name' => 'provider-statements', 'display_name' => 'Statements', 'guard_name' => 'admin', 'group_name' => 'CCM'],
            ['name' => 'provider-services', 'display_name' => 'CCM Services', 'guard_name' => 'admin', 'group_name' => 'CCM'],
            ['name' => 'provider-service-update', 'display_name' => 'CCM Service Update', 'guard_name' => 'admin', 'group_name' => 'CCM'],
            ['name' => 'provider-service-delete', 'display_name' => 'CCM Service Delete', 'guard_name' => 'admin', 'group_name' => 'CCM'],
            ['name' => 'provider-documents', 'display_name' => 'CCM Documents', 'guard_name' => 'admin', 'group_name' => 'CCM'],
            ['name' => 'provider-document-edit', 'display_name' => 'CCM Document Edit', 'guard_name' => 'admin', 'group_name' => 'CCM'],
            ['name' => 'provider-document-delete', 'display_name' => 'CCM Document Delete', 'guard_name' => 'admin', 'group_name' => 'CCM'],

            //field-executive
            ['name' => 'field-executive-list', 'display_name' => 'Field Executive list', 'guard_name' => 'admin', 'group_name' => 'Field Executive'],
            ['name' => 'field-executive-create', 'display_name' => 'Create Field Executive', 'guard_name' => 'admin', 'group_name' => 'Field Executive'],
            ['name' => 'field-executive-edit', 'display_name' => 'Edit Field Executive', 'guard_name' => 'admin', 'group_name' => 'Field Executive'],
            ['name' => 'field-executive-delete', 'display_name' => 'Delete Field Executive', 'guard_name' => 'admin', 'group_name' => 'Field Executive'],
            ['name' => 'field-executive-status', 'display_name' => 'Field Executive Status', 'guard_name' => 'admin', 'group_name' => 'Field Executive'],
            ['name' => 'status-field-executive', 'display_name' => 'Status Field Executive ', 'guard_name' => 'admin', 'group_name' => 'Field Executive'],
            ['name' => 'field-executive-history', 'display_name' => 'Ride History', 'guard_name' => 'admin', 'group_name' => 'Field Executive'],
            ['name' => 'field-executive-statements', 'display_name' => 'Statements', 'guard_name' => 'admin', 'group_name' => 'Field Executive'],
            ['name' => 'field-executive-services', 'display_name' => 'Field Executive Services', 'guard_name' => 'admin', 'group_name' => 'Field Executive'],
            ['name' => 'field-executive-service-update', 'display_name' => 'Field Executive Service Update', 'guard_name' => 'admin', 'group_name' => 'Field Executive'],
            ['name' => 'field-executive-service-delete', 'display_name' => 'Field Executive Service Delete', 'guard_name' => 'admin', 'group_name' => 'Field Executive'],
            ['name' => 'field-executive-documents', 'display_name' => 'Field Executive Documents', 'guard_name' => 'admin', 'group_name' => 'Field Executive'],
            ['name' => 'field-executive-document-edit', 'display_name' => 'Field Executive Document Edit', 'guard_name' => 'admin', 'group_name' => 'Field Executive'],
            ['name' => 'field-executive-document-delete', 'display_name' => 'Field Executive Document Delete', 'guard_name' => 'admin', 'group_name' => 'Field Executive'],


            ['name' => 'dispatcher-list', 'display_name' => 'Dispatcher list', 'guard_name' => 'admin', 'group_name' => 'Dispatcher'],
            ['name' => 'dispatcher-create', 'display_name' => 'Create Dispatcher', 'guard_name' => 'admin', 'group_name' => 'Dispatcher'],
            ['name' => 'dispatcher-edit', 'display_name' => 'Edit Dispatcher', 'guard_name' => 'admin', 'group_name' => 'Dispatcher'],
            ['name' => 'dispatcher-delete', 'display_name' => 'Delete Dispatcher', 'guard_name' => 'admin', 'group_name' => 'Dispatcher'],

            ['name' => 'account-manager-list', 'display_name' => 'Account Manager list', 'guard_name' => 'admin', 'group_name' => 'Account Manager'],
            ['name' => 'account-manager-create', 'display_name' => 'Create Account Manager', 'guard_name' => 'admin', 'group_name' => 'Account Manager'],
            ['name' => 'account-manager-edit', 'display_name' => 'Edit Account Manager', 'guard_name' => 'admin', 'group_name' => 'Account Manager'],
            ['name' => 'account-manager-delete', 'display_name' => 'Delete Account Manager', 'guard_name' => 'admin', 'group_name' => 'Account Manager'],


            ['name' => 'dispute-manager-list', 'display_name' => 'Dispute Manager list', 'guard_name' => 'admin', 'group_name' => 'Dispute Manager'],
            ['name' => 'dispute-manager-create', 'display_name' => 'Create Dispute Manager', 'guard_name' => 'admin', 'group_name' => 'Dispute Manager'],
            ['name' => 'dispute-manager-edit', 'display_name' => 'Edit Dispute Manager', 'guard_name' => 'admin', 'group_name' => 'Dispute Manager'],
            ['name' => 'dispute-manager-delete', 'display_name' => 'Delete Dispute Manager', 'guard_name' => 'admin', 'group_name' => 'Dispute Manager'],

            ['name' => 'statements', 'display_name' => 'Statements', 'guard_name' => 'admin', 'group_name' => 'Statements'],

            ['name' => 'overall-service-statements', 'display_name' => 'Overall Service Statements', 'guard_name' => 'admin', 'group_name' => 'Statements'],
            ['name' => 'overall-order-statements', 'display_name' => 'Overall Order Statements', 'guard_name' => 'admin', 'group_name' => 'Statements'],
            ['name' => 'overall-user-history', 'display_name' => 'Overall User History', 'guard_name' => 'admin', 'group_name' => 'Statements'],
            ['name' => 'provider-earnings-statements', 'display_name' => 'Provider  Statements', 'guard_name' => 'admin', 'group_name' => 'Statements'],
            ['name' => 'overall-admin-transaction', 'display_name' => 'Overall Admin Transactions', 'guard_name' => 'admin', 'group_name' => 'Statements'],
            ['name' => 'overall-store-transaction', 'display_name' => 'Overall Pharmacy Transactions', 'guard_name' => 'admin', 'group_name' => 'Statements'],
          
            ['name' => 'settlements', 'display_name' => 'Settlements', 'guard_name' => 'admin', 'group_name' => 'Settlements'],

            ['name' => 'user-rating', 'display_name' => 'Patient Ratings', 'guard_name' => 'admin', 'group_name' => 'Patient Ratings'],
            ['name' => 'provider-rating', 'display_name' => 'CCM Ratings', 'guard_name' => 'admin', 'group_name' => 'CCM Ratings'],

    

            ['name' => 'promocodes-list', 'display_name' => 'Promocodes List', 'guard_name' => 'admin', 'group_name' => 'Promocodes'],
            ['name' => 'promocodes-create', 'display_name' => 'Create Promocode', 'guard_name' => 'admin', 'group_name' => 'Promocodes'],
            ['name' => 'promocodes-edit', 'display_name' => 'Edit Promocode', 'guard_name' => 'admin', 'group_name' => 'Promocodes'],
            ['name' => 'promocodes-delete', 'display_name' => 'Delete Promocode', 'guard_name' => 'admin', 'group_name' => 'Promocodes'],


            ['name' => 'documents-list', 'display_name' => 'Documents List', 'guard_name' => 'admin', 'group_name' => 'Documents'],
            ['name' => 'documents-create', 'display_name' => 'Create Document', 'guard_name' => 'admin', 'group_name' => 'Documents'],
            ['name' => 'documents-edit', 'display_name' => 'Edit Document', 'guard_name' => 'admin', 'group_name' => 'Documents'],
            ['name' => 'documents-delete', 'display_name' => 'Delete Document', 'guard_name' => 'admin', 'group_name' => 'Documents'],
            ['name' => 'documents-status', 'display_name' => 'Document Status', 'guard_name' => 'admin', 'group_name' => 'Documents'],

            ['name' => 'cancel-reasons-list', 'display_name' => 'Cancel Reason List', 'guard_name' => 'admin', 'group_name' => 'Cancel Reasons'],
            ['name' => 'cancel-reasons-create', 'display_name' => 'Create Reason', 'guard_name' => 'admin', 'group_name' => 'Cancel Reasons'],
            ['name' => 'cancel-reasons-edit', 'display_name' => 'Edit Reason', 'guard_name' => 'admin', 'group_name' => 'Cancel Reasons'],
            ['name' => 'cancel-reasons-delete', 'display_name' => 'Delete Reason', 'guard_name' => 'admin', 'group_name' => 'Cancel Reasons'],
            ['name' => 'cancel-reasons-status', 'display_name' => 'Reason Status', 'guard_name' => 'admin', 'group_name' => 'Cancel Reasons'],

            ['name' => 'notification-list', 'display_name' => 'Notifications List', 'guard_name' => 'admin', 'group_name' => 'Notifications'],
            ['name' => 'notification-create', 'display_name' => 'Create Notification', 'guard_name' => 'admin', 'group_name' => 'Notifications'],
            ['name' => 'notification-edit', 'display_name' => 'Edit Notification', 'guard_name' => 'admin', 'group_name' => 'Notifications'],
            ['name' => 'notification-delete', 'display_name' => 'Delete Notification', 'guard_name' => 'admin', 'group_name' => 'Notifications'],
            // ['name' => 'notification-status', 'display_name' => 'Notification Status', 'guard_name' => 'admin', 'group_name' => 'Notifications'],


            ['name' => 'role-list', 'display_name' => 'Role list', 'guard_name' => 'admin', 'group_name' => 'Role'],
            ['name' => 'role-create', 'display_name' => 'Create Role', 'guard_name' => 'admin', 'group_name' => 'Role'],
            ['name' => 'role-edit', 'display_name' => 'Edit Role', 'guard_name' => 'admin', 'group_name' => 'Role'],
            ['name' => 'role-delete', 'display_name' => 'Delete Role', 'guard_name' => 'admin', 'group_name' => 'Role'],

            ['name' => 'sub-admin-list', 'display_name' => 'Sub Admins List', 'guard_name' => 'admin', 'group_name' => 'Sub Admins'],
            ['name' => 'sub-admin-create', 'display_name' => 'Create Sub Admin', 'guard_name' => 'admin', 'group_name' => 'Sub Admins'],
            ['name' => 'sub-admin-edit', 'display_name' => 'Edit Sub Admin', 'guard_name' => 'admin', 'group_name' => 'Sub Admins'],
            ['name' => 'sub-admin-delete', 'display_name' => 'Delete Sub Admin', 'guard_name' => 'admin', 'group_name' => 'Sub Admins'],
            ['name' => 'sub-admin-status', 'display_name' => 'Status Sub Admin', 'guard_name' => 'admin', 'group_name' => 'Sub Admins'],


            ['name' => 'payment-history', 'display_name' => 'Payment History List', 'guard_name' => 'admin', 'group_name' => 'Payment'],

            ['name' => 'payment-settings', 'display_name' => 'Payment Settings List', 'guard_name' => 'admin', 'group_name' => 'Payment'],


            ['name' => 'site-settings', 'display_name' => 'Site Settings', 'guard_name' => 'admin', 'group_name' => 'Settings'],

            ['name' => 'account-settings', 'display_name' => 'Account Settings', 'guard_name' => 'admin', 'group_name' => 'Settings'],

            ['name' => 'transalations', 'display_name' => 'Translations', 'guard_name' => 'admin', 'group_name' => 'Settings'],

            ['name' => 'change-password', 'display_name' => 'Change Password', 'guard_name' => 'admin', 'group_name' => 'Settings'],

            ['name' => 'cms-pages', 'display_name' => 'CMS Pages', 'guard_name' => 'admin', 'group_name' => 'Pages'],

            ['name' => 'help', 'display_name' => 'Help', 'guard_name' => 'admin', 'group_name' => 'Pages'],

            ['name' => 'custom-push', 'display_name' => 'Custom Push', 'guard_name' => 'admin', 'group_name' => 'Others'],
            ['name' => 'add-custom-push', 'display_name' => 'Add Custom Push', 'guard_name' => 'admin', 'group_name' => 'Others'],

            ['name' => 'db-backup', 'display_name' => 'DB Backup', 'guard_name' => 'admin', 'group_name' => 'Others'],



            ['name' => 'service', 'display_name' => 'SERVICE', 'guard_name' => 'admin', 'group_name' => 'SERVICE'],

            ['name' => 'add-service-categories', 'display_name' => 'Add Service Categories', 'guard_name' => 'admin', 'group_name' => 'SERVICE'],
            ['name' => 'edit-service-categories', 'display_name' => 'Edit Service Categories', 'guard_name' => 'admin', 'group_name' => 'SERVICE'],
            ['name' => 'service-categories-status', 'display_name' => 'Service Categories Status', 'guard_name' => 'admin', 'group_name' => 'SERVICE'],

            ['name' => 'add-sub-service-categories', 'display_name' => 'Add Sub Service Categories', 'guard_name' => 'admin', 'group_name' => 'SERVICE'],
            ['name' => 'edit-sub-service-categories', 'display_name' => 'Edit Sub Service Categories', 'guard_name' => 'admin', 'group_name' => 'SERVICE'],
            ['name' => 'sub-service-categories-status', 'display_name' => 'SubService Categories Status', 'guard_name' => 'admin', 'group_name' => 'SERVICE'],

            ['name' => 'add-service-categories', 'display_name' => 'Add Service Categories', 'guard_name' => 'admin', 'group_name' => 'SERVICE'],
            ['name' => 'edit-service-categories', 'display_name' => 'Edit Service Categories', 'guard_name' => 'admin', 'group_name' => 'SERVICE'],
            ['name' => 'service-categories-status', 'display_name' => 'Service Categories Status', 'guard_name' => 'admin', 'group_name' => 'SERVICE'],

            ['name' => 'add-service', 'display_name' => 'Add Service', 'guard_name' => 'admin', 'group_name' => 'SERVICE'],
            ['name' => 'edit-service', 'display_name' => 'Edit Service', 'guard_name' => 'admin', 'group_name' => 'SERVICE'],
            ['name' => 'service-price', 'display_name' => 'Service Price', 'guard_name' => 'admin', 'group_name' => 'SERVICE'],
            ['name' => 'service-status', 'display_name' => 'Service Status', 'guard_name' => 'admin', 'group_name' => 'SERVICE'],

            ['name' => 'service-request-history', 'display_name' => 'Service Request history View', 'guard_name' => 'admin', 'group_name' => 'SERVICE'],


            ['name' => 'store', 'display_name' => 'PHARMACY', 'guard_name' => 'admin', 'group_name' => 'PHARMACY'],

            ['name' => 'payroll', 'display_name' => 'Payroll', 'guard_name' => 'admin', 'group_name' => 'Payroll'],
            ['name' => 'payroll-list', 'display_name' => 'Payroll List', 'guard_name' => 'admin', 'group_name' => 'Payroll'],
            ['name' => 'payroll-create', 'display_name' => ' Create Payroll', 'guard_name' => 'admin', 'group_name' => 'Payroll'],
            ['name' => 'payroll-edit', 'display_name' => ' Edit Payroll', 'guard_name' => 'admin', 'group_name' => 'Payroll'],
            ['name' => 'payroll-delete', 'display_name' => 'Delete Payroll', 'guard_name' => 'admin', 'group_name' => 'Payroll'],
            ['name' => 'payroll-downlod', 'display_name' => 'Downlod Payroll', 'guard_name' => 'admin', 'group_name' => 'Payroll'],

            // ZONES
            ['name' => 'zone-list', 'display_name' => 'Zone List', 'guard_name' => 'admin', 'group_name' => 'Zone '],
            ['name' => 'zone-create', 'display_name' => 'Create Zone', 'guard_name' => 'admin', 'group_name' => 'Zone '],
            ['name' => 'zone-edit', 'display_name' => 'Edit Zone', 'guard_name' => 'admin', 'group_name' => 'Zone '],
            ['name' => 'zone-delete', 'display_name' => 'Delete Zone', 'guard_name' => 'admin', 'group_name' => 'Zone '],
            ['name' => 'zone-status', 'display_name' => 'Status Zone', 'guard_name' => 'admin', 'group_name' => 'Zone '],
           

            ['name' => 'add-shop-type', 'display_name' => 'Add Pharmacy Type', 'guard_name' => 'admin', 'group_name' => 'PHARMACY'],
            ['name' => 'edit-shop-type', 'display_name' => 'Edit Pharmacy Type', 'guard_name' => 'admin', 'group_name' => 'PHARMACY'],
            ['name' => 'shop-type-price', 'display_name' => 'Pharmacy Type Price', 'guard_name' => 'admin', 'group_name' => 'PHARMACY'],
            ['name' => 'shop-type-status', 'display_name' => 'Pharmacy Type Status', 'guard_name' => 'admin', 'group_name' => 'PHARMACY'],

            ['name' => 'add-cuisine', 'display_name' => 'Add Cuisine', 'guard_name' => 'admin', 'group_name' => 'PHARMACY'],
            ['name' => 'edit-cuisine', 'display_name' => 'Edit Cuisine', 'guard_name' => 'admin', 'group_name' => 'PHARMACY'],
            ['name' => 'cuisine-status', 'display_name' => 'Cuisine Status', 'guard_name' => 'admin', 'group_name' => 'PHARMACY'],

            ['name' => 'add-shop', 'display_name' => 'Add Pharmacy', 'guard_name' => 'admin', 'group_name' => 'PHARMACY'],
            ['name' => 'edit-shop', 'display_name' => 'Edit Pharmacy', 'guard_name' => 'admin', 'group_name' => 'PHARMACY'],
            ['name' => 'shop-status', 'display_name' => 'Pharmacy Status', 'guard_name' => 'admin', 'group_name' => 'PHARMACY'],
            ['name' => 'shop-category', 'display_name' => 'Pharmacy Category', 'guard_name' => 'admin', 'group_name' => 'PHARMACY'],
            ['name' => 'shop-items', 'display_name' => 'Pharmacy Items', 'guard_name' => 'admin', 'group_name' => 'PHARMACY'],
            ['name' => 'shop-log', 'display_name' => 'Pharmacy Logs', 'guard_name' => 'admin', 'group_name' => 'PHARMACY'],
            ['name' => 'shop-wallet-details', 'display_name' => 'Pharmacy Wallet Details', 'guard_name' => 'admin', 'group_name' => 'PHARMACY'],
            ['name' => 'shop-request-history', 'display_name' => 'Pharmacy Request History', 'guard_name' => 'admin', 'group_name' => 'PHARMACY'],

            ['name' => 'add-country', 'display_name' => 'Add Country', 'guard_name' => 'admin', 'group_name' => 'BUSINESS COUNTRY'],
            ['name' => 'edit-country', 'display_name' => 'Edit Country', 'guard_name' => 'admin', 'group_name' => 'BUSINESS COUNTRY'],
            ['name' => 'country-status', 'display_name' => 'Country Status', 'guard_name' => 'admin', 'group_name' => 'BUSINESS COUNTRY'],
            ['name' => 'country-bank-form', 'display_name' => 'Country Bank Form', 'guard_name' => 'admin', 'group_name' => 'BUSINESS COUNTRY'],

            ['name' => 'add-city', 'display_name' => 'Add City', 'guard_name' => 'admin', 'group_name' => 'BUSINESS CITY'],
            ['name' => 'edit-city', 'display_name' => 'Edit City', 'guard_name' => 'admin', 'group_name' => 'BUSINESS CITY'],
            ['name' => 'delete-city', 'display_name' => 'Delete City', 'guard_name' => 'admin', 'group_name' => 'BUSINESS CITY'],

            ['name' => 'add-menu', 'display_name' => 'Add Menu', 'guard_name' => 'admin', 'group_name' => 'MENUS'],
            ['name' => 'edit-menu', 'display_name' => 'Edit Menu', 'guard_name' => 'admin', 'group_name' => 'MENUS'],
            ['name' => 'delete-menu', 'display_name' => 'Delete Menu', 'guard_name' => 'admin', 'group_name' => 'MENUS'],
            ['name' => 'menu-city', 'display_name' => 'Menu City', 'guard_name' => 'admin', 'group_name' => 'MENUS'],

            // ['name' => 'delivery', 'display_name' => 'Deliveries', 'guard_name' => 'admin', 'group_name' => 'Delivery'],
            // ['name' => 'add-delivery-vehicle-type', 'display_name' => 'Add Vehicle Type', 'guard_name' => 'admin', 'group_name' => 'Delivery'],
            // ['name' => 'edit-delivery-vehicle-type', 'display_name' => 'Edit Vehicle Type', 'guard_name' => 'admin', 'group_name' => 'Delivery'],
            // ['name' => 'delivery-vehicle-type-price', 'display_name' => 'Vehicle Type Price', 'guard_name' => 'admin', 'group_name' => 'Delivery'],
            // ['name' => 'delivery-vehicle-type-status', 'display_name' => 'Vehicle Type Status', 'guard_name' => 'admin', 'group_name' => 'Delivery'],

            // ['name' => 'add-delivery-type', 'display_name' => 'Add Delivery Type', 'guard_name' => 'admin', 'group_name' => 'Delivery'],
            // ['name' => 'edit-delivery-type', 'display_name' => 'Edit Delivery Type', 'guard_name' => 'admin', 'group_name' => 'Delivery'],
            // ['name' => 'delivery-type-status', 'display_name' => 'Delivery Type Status', 'guard_name' => 'admin', 'group_name' => 'Delivery'],

            // ['name' => 'add-delivery-package', 'display_name' => 'Add Package Type', 'guard_name' => 'admin', 'group_name' => 'Delivery'],
            // ['name' => 'edit-delivery-package', 'display_name' => 'Edit Package Type', 'guard_name' => 'admin', 'group_name' => 'Delivery'],
            // ['name' => 'delivery-package-status', 'display_name' => 'Package Type Status', 'guard_name' => 'admin', 'group_name' => 'Delivery'],

            // ['name' => 'delivery-request-history-view', 'display_name' => 'Delivery Reqeust history view', 'guard_name' => 'admin', 'group_name' => 'Delivery'],

            // ['name' => 'payroll', 'display_name' => 'Payroll', 'guard_name' => 'admin', 'group_name' => 'Payroll']
        ]);

        $admin_permissions = Permission::select('id')->get();

        $admin->syncPermissions($admin_permissions->toArray());

        $permission = [];

        foreach ($admin_permissions as $admin_permission) {
            $permission[] = $admin_permission->id;
        }

        $dispatcher = Role::where('name', 'DISPATCHER')->first();

        $dispatcher_permissions = Permission::select('id')->whereIn('name', ['dispatcher-panel','dispatcher-panel-add','account-settings','change-password'])->get();

        $dispatcher->syncPermissions($dispatcher_permissions->toArray());

        $dispute = Role::where('name', 'DISPUTE')->first();

        $dispute_permissions = Permission::select('id')->whereIn('id', ['dispute-list','dispute-create','dispute-edit','dispute-delete','dispute-status','account-settings','change-password'])->get();

        $dispute->syncPermissions($dispute_permissions->toArray());

        $account = Role::where('name', 'ACCOUNT')->first();

        $account_permissions = Permission::select('id')->whereIn('id', ['dashboard-menus','wallet-summary','recent-rides','statements','account-settings','change-password'])->get();

        $account->syncPermissions($account_permissions->toArray());

    	Schema::enableForeignKeyConstraints();
    }
}
