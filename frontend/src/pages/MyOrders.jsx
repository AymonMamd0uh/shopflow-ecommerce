import { useEffect, useState } from "react";
import { Link } from "react-router-dom";
import { Package, ArrowLeft, ShoppingBag } from "lucide-react";
import Navbar from "../components/Navbar";
import api from "../services/api";

export default function MyOrders() {
  const [orders, setOrders] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");

  useEffect(() => {
    const fetchOrders = async () => {
      try {
        const response = await api.get("/orders");

        const data = response.data.data;
        setOrders(Array.isArray(data) ? data : data?.data || []);
      } catch (err) {
        setError(err.response?.data?.message || "Unable to load your orders.");
      } finally {
        setLoading(false);
      }
    };

    fetchOrders();
  }, []);

  return (
    <div className="min-h-screen bg-slate-50">
      <Navbar />

      <main className="mx-auto max-w-6xl px-4 py-10 sm:px-6">
        <Link
          to="/"
          className="mb-6 inline-flex items-center gap-2 text-sm font-semibold text-blue-600 hover:text-blue-700"
        >
          <ArrowLeft size={16} />
          Continue Shopping
        </Link>

        <div className="mb-8">
          <h1 className="text-3xl font-bold text-slate-900">My Orders</h1>
          <p className="mt-2 text-slate-500">Track and manage your orders.</p>
        </div>

        {loading ? (
          <p className="py-16 text-center text-slate-500">
            Loading your orders...
          </p>
        ) : error ? (
          <div className="rounded-xl bg-red-50 p-5 text-red-600">{error}</div>
        ) : orders.length === 0 ? (
          <div className="rounded-2xl border border-slate-200 bg-white py-20 text-center">
            <ShoppingBag size={56} className="mx-auto mb-4 text-slate-300" />
            <h2 className="text-xl font-bold text-slate-800">No orders yet</h2>
            <p className="mt-2 text-slate-500">Your orders will appear here.</p>
            <Link
              to="/"
              className="mt-6 inline-block rounded-xl bg-blue-600 px-6 py-3 font-semibold text-white hover:bg-blue-700"
            >
              Browse Products
            </Link>
          </div>
        ) : (
          <div className="space-y-4">
            {orders.map((order) => (
              <Link
                to={`/orders/${order.id}`}
                key={order.id}
                className="block rounded-2xl border border-slate-200 bg-white p-6 transition hover:border-blue-300 hover:shadow-md"
              >
                <div className="flex items-center gap-4">
                  <div className="rounded-xl bg-blue-50 p-3">
                    <Package size={25} className="text-blue-600" />
                  </div>

                  <div>
                    <h2 className="font-bold text-slate-900">
                      Order #{order.id}
                    </h2>
                    <p className="mt-1 text-sm text-slate-500">
                      {order.created_at
                        ? new Date(order.created_at).toLocaleDateString()
                        : "Date unavailable"}
                    </p>
                  </div>
                </div>

                <div className="flex flex-wrap items-center gap-5">
                  <div>
                    <p className="text-xs text-slate-500">Total</p>
                    <p className="mt-1 font-bold text-slate-900">
                      {Number(order.total).toLocaleString("en-US")} EGP
                    </p>
                  </div>

                  <div>
                    <p className="text-xs text-slate-500">Status</p>
                    <span className="mt-1 inline-block rounded-full bg-blue-50 px-3 py-1 text-sm font-semibold capitalize text-blue-700">
                      {order.status}
                    </span>
                  </div>
                </div>
              </Link>
            ))}
          </div>
        )}
      </main>
    </div>
  );
}
