import { NavLink, Outlet, Link } from "react-router-dom";
import {
  LayoutDashboard,
  Package,
  FolderOpen,
  ClipboardList,
  Store,
} from "lucide-react";

const links = [
  {
    name: "Dashboard",
    path: "/admin/dashboard",
    icon: LayoutDashboard,
  },
  {
    name: "Orders",
    path: "/admin/orders",
    icon: ClipboardList,
  },
  {
    name: "Products",
    path: "/admin/products",
    icon: Package,
  },
  {
    name: "Categories",
    path: "/admin/categories",
    icon: FolderOpen,
  },
];

export default function AdminLayout() {
  return (
    <div className="min-h-screen bg-slate-50 md:flex">
      <aside className="w-full border-r border-slate-200 bg-white md:min-h-screen md:w-64">
        <div className="border-b border-slate-200 p-6">
          <Link to="/" className="text-2xl font-extrabold text-slate-900">
            ShopFlow<span className="text-blue-600">.</span>
          </Link>
          <p className="mt-2 text-xs font-semibold uppercase tracking-wider text-slate-400">
            Admin Dashboard
          </p>
        </div>

        <nav className="space-y-2 p-4">
          {links.map((link) => {
            const Icon = link.icon;

            return (
              <NavLink
                key={link.path}
                to={link.path}
                className={({ isActive }) =>
                  `flex items-center gap-3 rounded-xl px-4 py-3 font-semibold transition ${
                    isActive
                      ? "bg-blue-600 text-white"
                      : "text-slate-600 hover:bg-slate-100"
                  }`
                }
              >
                <Icon size={19} />
                {link.name}
              </NavLink>
            );
          })}
        </nav>

        <div className="border-t border-slate-200 p-4">
          <Link
            to="/"
            className="flex items-center gap-3 rounded-xl px-4 py-3 font-semibold text-slate-600 hover:bg-slate-100"
          >
            <Store size={19} />
            Back to Store
          </Link>
        </div>
      </aside>

      <div className="min-w-0 flex-1">
        <Outlet />
      </div>
    </div>
  );
}
