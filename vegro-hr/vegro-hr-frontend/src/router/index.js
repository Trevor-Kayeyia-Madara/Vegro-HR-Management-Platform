import { createRouter, createWebHistory } from "vue-router"

import Landing from "../pages/Landing/Landing.vue"
import Contact from "../pages/Contact/Contact.vue"
import Pricing from "../pages/Pricing/Pricing.vue"
import Login from "../pages/Login/Login.vue"
import ForgotPassword from "../pages/Auth/ForgotPassword.vue"
import ResetPassword from "../pages/Auth/ResetPassword.vue"
import DashboardLayout from "../layouts/DashboardLayout.vue"
import DashboardHome from "../pages/Dashboard/Dashboard.vue"
import SuperAdminDashboard from "../pages/SuperAdmin/Dashboard.vue"
import SuperAdminRoles from "../pages/SuperAdmin/Roles.vue"
import SuperAdminCompanies from "../pages/SuperAdmin/Companies.vue"
import SuperAdminUsers from "../pages/SuperAdmin/Users.vue"
import SuperAdminBilling from "../pages/SuperAdmin/Billing.vue"
import HrDashboard from "../pages/HR/Dashboard.vue"
import FinanceDashboard from "../pages/Finance/Dashboard.vue"
import FinanceManagerDashboard from "../pages/FinanceManager/Dashboard.vue"
import EmployeeDashboard from "../pages/Employee/Dashboard.vue"
import ManagerDashboard from "../pages/Manager/Dashboard.vue"
import DirectorDashboard from "../pages/Director/Dashboard.vue"
import ManagerTeam from "../pages/Manager/Team.vue"
import ProtectedRoute from "../components/ProtectedRoute.vue"
import Employees from "../pages/Employees/Employees.vue"
import OrgChart from "../pages/OrgChart/OrgChart.vue"
import Projects from "../pages/Projects/Projects.vue"
import Payroll from "../pages/Payroll/Payroll.vue"
import Attendance from "../pages/Attendance/Attendance.vue"
import Leaves from "../pages/Leaves/Leaves.vue"
import Payslips from "../pages/Payslips/Payslips.vue"
import Users from "../pages/Users/Users.vue"
import Profile from "../pages/Profile/Profile.vue"
import TaxProfiles from "../pages/TaxProfiles/TaxProfiles.vue"
import Settings from "../pages/Settings/Settings.vue"
import Roles from "../pages/Roles/Roles.vue"
import RoleMatrix from "../pages/Roles/RoleMatrix.vue"
import Reports from "../pages/Reports/Reports.vue"
import Dashboards from "../pages/Dashboards/Dashboards.vue"
import Recruitment from "../pages/Ats/Recruitment.vue"
import Feedback from "../pages/Feedback/Feedback.vue"
import Onboarding from "../pages/Onboarding/Onboarding.vue"
import Compliance from "../pages/Compliance/Compliance.vue"
import Audits from "../pages/Audits/Audits.vue"


const routes = [
  {
    path: "/",
    name: "Landing",
    component: Landing
  },
  {
    path: "/contact",
    name: "Contact",
    component: Contact
  },
  {
    path: "/pricing",
    name: "Pricing",
    component: Pricing
  },
  {
    path: "/login",
    name: "Login",
    component: Login
  },
  {
    path: "/forgot-password",
    name: "ForgotPassword",
    component: ForgotPassword
  },
  {
    path: "/reset-password",
    name: "ResetPassword",
    component: ResetPassword
  },
  {
    path: "/dashboard",
    component: ProtectedRoute,
    redirect: "/dashboard/home",
    children: [
      {
        path: "",
        component: DashboardLayout,
        children: [
          {
            path: "home",
            name: "DashboardHome",
            component: DashboardHome,
            meta: { roles: ['admin'], permissions: 'dashboard.view' }
          },
          {
            path: "super",
            name: "SuperAdminDashboard",
            component: SuperAdminDashboard,
            meta: { roles: ['superadmin'], permissions: 'dashboard.view' }
          },
          {
            path: "super/roles",
            name: "SuperAdminRoles",
            component: SuperAdminRoles,
            meta: { roles: ['superadmin'], permissions: 'dashboard.view' }
          },
          {
            path: "super/companies",
            name: "SuperAdminCompanies",
            component: SuperAdminCompanies,
            meta: { roles: ['superadmin'], permissions: 'dashboard.view' }
          },
          {
            path: "super/users",
            name: "SuperAdminUsers",
            component: SuperAdminUsers,
            meta: { roles: ['superadmin'], permissions: 'dashboard.view' }
          },
          {
            path: "super/billing",
            name: "SuperAdminBilling",
            component: SuperAdminBilling,
            meta: { roles: ['superadmin'], permissions: 'dashboard.view' }
          },
          {
            path: "hr",
            name: "HrDashboard",
            component: HrDashboard,
            meta: { roles: ['hr'], permissions: 'dashboard.view' }
          },
          {
            path: "finance",
            name: "FinanceDashboard",
            component: FinanceDashboard,
            meta: { roles: ['finance'], permissions: 'dashboard.view' }
          },
          {
            path: "finance-manager",
            name: "FinanceManagerDashboard",
            component: FinanceManagerDashboard,
            meta: { roles: ['financemanager'], permissions: 'dashboard.view' }
          },
          {
            path: "employee",
            name: "EmployeeDashboard",
            component: EmployeeDashboard,
            meta: { roles: ['employee'], permissions: 'dashboard.view' }
          },
          {
            path: "manager",
            name: "ManagerDashboard",
            component: ManagerDashboard,
            meta: { roles: ['manager'], permissions: 'dashboard.view' }
          },
          {
            path: "director",
            name: "DirectorDashboard",
            component: DirectorDashboard,
            meta: { roles: ['director', 'md'], permissions: 'dashboard.view' }
          },
          {
            path: "my-team",
            name: "ManagerTeam",
            component: ManagerTeam,
            meta: { roles: ['manager'], permissions: 'employees.view' }
          },
          {
            path: "employees",
            name: "Employees",
            component: Employees,
            meta: { roles: ['admin', 'hr'], permissions: 'employees.view' }
          },
          {
            path: "departments",
            redirect: "/dashboard/org-chart",
          },
          {
            path: "org-chart",
            name: "OrgChart",
            component: OrgChart,
            meta: { roles: ['hr'] }
          },
          {
            path: "projects",
            name: "Projects",
            component: Projects,
            meta: { roles: ['admin', 'hr', 'manager', 'employee', 'director', 'md'], permissions: 'projects.view' }
          },
          {
            path: "tax-profiles",
            name: "TaxProfiles",
            component: TaxProfiles,
            meta: { roles: ['admin', 'finance'], permissions: 'taxprofiles.view' }
          },
          {
            path: "users",
            name: "Users",
            component: Users,
            meta: { roles: ['admin'], permissions: 'users.manage' }
          },
          {
            path: "payroll",
            name: "Payroll",
            component: Payroll,
            meta: { roles: ['admin', 'hr', 'finance'], permissions: 'payroll.view' }
          },
          {
            path: "attendance",
            name: "Attendance",
            component: Attendance,
            meta: { roles: ['admin', 'hr'], permissions: 'attendance.view' }
          },
          {
            path: "leaves",
            name: "Leaves",
            component: Leaves,
            meta: { roles: ['admin', 'hr', 'manager', 'employee'], permissions: 'leaves.view' }
          },
          {
            path: "payslips",
            name: "Payslips",
            component: Payslips,
            meta: { roles: ['admin', 'hr', 'finance', 'employee'], permissions: 'payslips.view' }
          },
          {
            path: "profile",
            name: "Profile",
            component: Profile,
            meta: { roles: ['admin', 'hr', 'finance', 'manager', 'employee'], permissions: 'profile.view' }
          },
          {
            path: "settings",
            name: "Settings",
            component: Settings,
            meta: { roles: ['admin'], permissions: 'settings.manage' }
          },
          {
            path: "roles",
            name: "Roles",
            component: Roles,
            meta: { roles: ['admin'], permissions: 'roles.manage' }
          },
          {
            path: "role-matrix",
            name: "RoleMatrix",
            component: RoleMatrix,
            meta: { roles: ['admin'], permissions: 'roles.manage' }
          },
          {
            path: "reports",
            name: "Reports",
            component: Reports,
            meta: { roles: ['admin', 'hr', 'finance', 'director', 'md'] }
          },
          {
            path: "dashboards",
            name: "Dashboards",
            component: Dashboards,
            meta: { roles: ['admin', 'hr', 'finance', 'director', 'md'] }
          },
          {
            path: "dashboards/:dashboardId",
            name: "DashboardView",
            component: Dashboards,
            meta: { roles: ['admin', 'hr', 'finance', 'director', 'md'] }
          },
          {
            path: "recruitment",
            name: "Recruitment",
            component: Recruitment,
            meta: { roles: ['admin', 'hr', 'manager', 'director', 'md'], permissions: 'recruitment.view' }
          },
          {
            path: "feedback",
            name: "Feedback",
            component: Feedback,
            meta: { roles: ['admin', 'hr', 'finance', 'manager', 'employee', 'director', 'md'], permissions: 'feedback.submit' }
          },
          {
            path: "onboarding",
            name: "Onboarding",
            component: Onboarding,
            meta: { roles: ['admin', 'hr', 'finance', 'manager', 'employee', 'director', 'md'], permissions: 'onboarding.view' }
          },
          {
            path: "compliance",
            name: "Compliance",
            component: Compliance,
            meta: { roles: ['admin', 'hr', 'finance', 'manager', 'director', 'md'], permissions: 'compliance.view' }
          },
          {
            path: "audits",
            name: "Audits",
            component: Audits,
            meta: { roles: ['admin', 'hr', 'finance', 'director', 'md'], permissions: 'audits.view' }
          }
        ]
      }
    ]
  },
]

const router = createRouter({
  history: createWebHistory(),
  routes
})


export default router
