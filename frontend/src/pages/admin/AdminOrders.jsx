import { useEffect, useState } from "react";
import { Link } from "react-router-dom";
import { ClipboardList, Eye, RefreshCw } from "lucide-react";
import api from "../../services/api";

export default function AdminOrders() {
  const [orders, setOrders] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");
  const handleRefresh = () => {
    setLoading(true);
    setError("");
    fetchOrders();
  };
  const fetchOrders = async () => {
    try {
      const response = await api.get("/admin/orders");
      const data = response.data.data;

      setOrders(Array.isArray(data) ? data : data?.data || []);
    } catch (err) {
      setError(err.response?.data?.message || "Failed to load orders.");
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchOrders();
  }, []);

  const getStatusColor = (status) => {
    const colors = {
      pending: "bg-yellow-100 text-yellow-700",
      confirmed: "bg-blue-100 text-blue-700",
      processing: "bg-purple-100 text-purple-700",
      shipped: "bg-indigo-100 text-indigo-700",
      delivered: "bg-green-100 text-green-700",
      cancelled: "bg-red-100 text-red-700",
      returned: "bg-slate-200 text-slate-700",
    };

    return colors[status] || "bg-slate-100 text-slate-700";
  };

  return (
    <div className="min-h-screen bg-slate-50">
      <main className="mx-auto max-w-7xl px-6 py-10">
        <div className="mb-8 flex flex-wrap items-center justify-between gap-4">
          <div>
            <div className="mb-2 flex items-center gap-2 text-blue-600">
              <ClipboardList size={22} />
              <span className="font-semibold">Administration</span>
            </div>

            <h1 className="text-3xl font-extrabold text-slate-900">
              Orders Management
            </h1>

            <p className="mt-2 text-slate-500">
              View and manage customer orders.
            </p>
          </div>

          <button
            onClick={handleRefresh}
            disabled={loading}
            className="flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-3 font-semibold text-slate-700 hover:bg-slate-100 disabled:opacity-50"
          >
            <RefreshCw size={17} />
            Refresh
          </button>
        </div>

        {error && (
          <div className="mb-6 rounded-xl bg-red-50 p-4 text-red-700">
            {error}
          </div>
        )}

        <div className="mb-6 rounded-2xl border border-slate-200 bg-white p-6">
          <p className="text-sm font-medium text-slate-500">Total Orders</p>
          <p className="mt-2 text-3xl font-extrabold text-slate-900">
            {orders.length}
          </p>
        </div>

        <div className="overflow-hidden rounded-2xl border border-slate-200 bg-white">
          {loading ? (
            <div className="p-10 text-center text-slate-500">
              Loading orders...
            </div>
          ) : orders.length === 0 ? (
            <div className="p-10 text-center text-slate-500">
              No orders found.
            </div>
          ) : (
            <div className="overflow-x-auto">
              <table className="w-full text-left">
                <thead className="bg-slate-50 text-sm text-slate-500">
                  <tr>
                    <th className="px-6 py-4">Order</th>
                    <th className="px-6 py-4">Customer</th>
                    <th className="px-6 py-4">Date</th>
                    <th className="px-6 py-4">Total</th>
                    <th className="px-6 py-4">Status</th>
                    <th className="px-6 py-4">Action</th>
                  </tr>
                </thead>

                <tbody className="divide-y divide-slate-100">
                  {orders.map((order) => (
                    <tr key={order.id} className="hover:bg-slate-50">
                      <td className="px-6 py-4 font-bold text-slate-900">
                        #{order.order_number || order.id}
                      </td>

                      <td className="px-6 py-4 text-slate-700">
                        {order.customer_name}
                      </td>

                      <td className="px-6 py-4 text-sm text-slate-500">
                        {new Date(order.created_at).toLocaleDateString()}
                      </td>

                      <td className="px-6 py-4 font-semibold text-slate-900">
                        {order.total} EGP
                      </td>

                      <td className="px-6 py-4">
                        <span
                          className={`rounded-full px-3 py-1 text-xs font-bold capitalize ${getStatusColor(order.status)}`}
                        >
                          {order.status}
                        </span>
                      </td>

                      <td className="px-6 py-4">
                        <Link
                          to={`/admin/orders/${order.id}`}
                          className="inline-flex items-center gap-2 rounded-lg bg-blue-50 px-3 py-2 text-sm font-semibold text-blue-600 hover:bg-blue-100"
                        >
                          <Eye size={16} />
                          View
                        </Link>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          )}
        </div>
      </main>
    </div>
  );
}
