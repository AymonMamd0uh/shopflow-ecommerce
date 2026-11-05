import { useEffect, useState } from "react";
import { Link, useNavigate } from "react-router-dom";
import {
  ShoppingBag,
  ShoppingCart,
  LogOut,
  User,
  ClipboardList,
  ShieldCheck,
} from "lucide-react";
import api from "../services/api";
import { useCart } from "../context/useCart";

export default function Navbar() {
  const [user, setUser] = useState(null);
  const [loading, setLoading] = useState(true);
  const navigate = useNavigate();

  const { cart } = useCart();

  const cartCount =
    cart?.items?.reduce((total, item) => total + item.quantity, 0) || 0;

  useEffect(() => {
    const fetchUser = async () => {
      const token = localStorage.getItem("token");

      if (!token) {
        setLoading(false);
        return;
      }

      try {
        const response = await api.get("/auth/me");
        setUser(
          response.data.data?.user || response.data.data || response.data.user,
        );
      } catch {
        localStorage.removeItem("token");
        setUser(null);
      } finally {
        setLoading(false);
      }
    };

    fetchUser();
  }, []);

  const handleLogout = async () => {
    try {
      await api.post("/auth/logout");
    } catch (error) {
      console.error("Logout request failed:", error);
    } finally {
      localStorage.removeItem("token");
      setUser(null);
      navigate("/login");
    }
  };

  return (
    <header className="border-b border-slate-200 bg-white">
      <div className="mx-auto flex max-w-7xl items-center justify-between px-6 py-5">
        <Link to="/" className="flex items-center gap-2">
          <ShoppingBag className="text-blue-600" size={28} />
          <span className="text-2xl font-extrabold text-slate-900">
            ShopFlow<span className="text-blue-600">.</span>
          </span>
        </Link>

        <div className="flex items-center gap-3">
          <Link
            to="/cart"
            className="relative rounded-xl bg-blue-50 p-3 text-blue-600 hover:bg-blue-100"
          >
            <ShoppingCart size={21} />

            {cartCount > 0 && (
              <span className="absolute -right-2 -top-2 flex h-5 min-w-5 items-center justify-center rounded-full bg-red-500 px-1 text-xs font-bold text-white">
                {cartCount}
              </span>
            )}
          </Link>

          {!loading &&
            (user ? (
              <>
                {user.role === "admin" && (
                  <Link
                    to="/admin/orders"
                    className="flex items-center gap-2 rounded-xl bg-slate-100 px-3 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-200"
                  >
                    <ShieldCheck size={18} />
                    <span className="hidden sm:inline">Admin</span>
                  </Link>
                )}
                <Link
                  to="/my-orders"
                  className="flex items-center gap-2 rounded-xl bg-blue-50 px-3 py-3 text-sm font-semibold text-blue-600 hover:bg-blue-100"
                >
                  <ClipboardList size={18} />
                  <span className="hidden sm:inline">My Orders</span>
                </Link>

                <div className="flex items-center gap-3">
                  <span className="hidden items-center gap-2 text-sm font-semibold text-slate-700 lg:flex">
                    <User size={18} className="text-blue-600" />
                    {user.name}
                  </span>

                  <button
                    onClick={handleLogout}
                    className="flex items-center gap-2 rounded-xl bg-red-50 px-4 py-3 text-sm font-semibold text-red-600 hover:bg-red-100"
                  >
                    <LogOut size={17} />
                    <span className="hidden sm:inline">Logout</span>
                  </button>
                </div>
              </>
            ) : (
              <Link
                to="/login"
                className="rounded-xl bg-blue-600 px-5 py-3 text-sm font-semibold text-white hover:bg-blue-700"
              >
                Sign In
              </Link>
            ))}
        </div>
      </div>
    </header>
  );
}
