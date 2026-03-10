import { createRouter, createWebHistory } from "vue-router"

import Login from "../pages/Login/Login.vue"
import DashboardLayout from "../layouts/DashboardLayout.vue"
import DashboardHome from "../pages/Dashboard/Dashboard.vue"
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


const routes = [
  {
    path: "/",
    redirect: "/login"
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
            component: DashboardHome
          },
          {
            path: "employees",
            name: "Employees",
            component: Employees,
            meta: { roles: ['admin', 'hr'] }
          },
          {
            path: "departments",
            name: "Departments",
            component: Departments,
            meta: { roles: ['admin', 'hr'] }
          },
          {
            path: "tax-profiles",
            name: "TaxProfiles",
            component: TaxProfiles,
            meta: { roles: ['admin'] }
          },
          {
            path: "users",
            name: "Users",
            component: Users,
            meta: { roles: ['admin'] }
          },
          {
            path: "payroll",
            name: "Payroll",
            component: Payroll,
            meta: { roles: ['admin', 'hr', 'finance'] }
          },
          {
            path: "attendance",
            name: "Attendance",
            component: Attendance,
            meta: { roles: ['admin', 'hr'] }
          },
          {
            path: "leaves",
            name: "Leaves",
            component: Leaves,
            meta: { roles: ['admin', 'hr', 'manager', 'employee'] }
          },
          {
            path: "payslips",
            name: "Payslips",
            component: Payslips,
            meta: { roles: ['admin', 'hr', 'finance'] }
          },
          {
            path: "profile",
            name: "Profile",
            component: Profile,
            meta: { roles: ['admin', 'hr', 'finance', 'manager', 'employee'] }
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
