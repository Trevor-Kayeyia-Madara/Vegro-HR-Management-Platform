import { createRouter, createWebHistory } from "vue-router"

import Landing from "../pages/Landing/Landing.vue"
import Contact from "../pages/Contact/Contact.vue"
import Pricing from "../pages/Pricing/Pricing.vue"
import Login from "../pages/Login/Login.vue"
import DashboardLayout from "../layouts/DashboardLayout.vue"
import DashboardHome from "../pages/Dashboard/Dashboard.vue"
import HrDashboard from "../pages/HR/Dashboard.vue"
import FinanceDashboard from "../pages/Finance/Dashboard.vue"
import EmployeeDashboard from "../pages/Employee/Dashboard.vue"
import ManagerDashboard from "../pages/Manager/Dashboard.vue"
import DirectorDashboard from "../pages/Director/Dashboard.vue"
import ProtectedRoute from "../components/ProtectedRoute.vue"
import Employees from "../pages/Employees/Employees.vue"
import Departments from "../pages/Departments/Departments.vue"
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
            path: "employees",
            name: "Employees",
            component: Employees,
            meta: { roles: ['admin', 'hr'], permissions: 'employees.view' }
          },
          {
            path: "departments",
            name: "Departments",
            component: Departments,
            meta: { roles: ['admin', 'hr'], permissions: 'departments.view' }
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
