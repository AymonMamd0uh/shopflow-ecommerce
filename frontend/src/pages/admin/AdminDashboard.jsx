import { useCallback, useEffect, useState } from "react";
import { Link } from "react-router-dom";
import {
  ShoppingBag,
  Package,
  FolderOpen,
  Wallet,
  Clock,
  Eye,
  RefreshCw,
} from "lucide-react";
import api from "../../services/api";

export default function AdminDashboard() {
  const [stats, setStats] = useState({
    orders: [],
    products: 0,
    categories: 0,
    revenue: 0,
    pending: 0,
  });

  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");

const fetchDashboardData = useCallback(async () => {
  try {
    const [ordersResponse, productsResponse, categoriesResponse] =
      await Promise.all([
        api.get("/admin/orders"),
        api.get("/products"),
        api.get("/categories"),
      ]);

    const extractList = (response) => {
      const data = response.data.data;
      return Array.isArray(data) ? data : data?.data || [];
    };

    const orders = extractList(ordersResponse);
    const products = extractList(productsResponse);
    const categories = extractList(categoriesResponse);

    const paidRevenue = orders
      .filter((order) => order.payment_status === "paid")
      .reduce((total, order) => total + Number(order.total || 0), 0);

    const pendingOrders = orders.filter(
      (order) => order.status === "pending"
    ).length;

    setStats({
      orders: [...orders].sort(
        (a, b) => new Date(b.created_at) - new Date(a.created_at)
      ),
      products: products.length,
      categories: categories.length,
      revenue: paidRevenue,
      pending: pendingOrders,
    });
  } catch (err) {
    setError(err.response?.data?.message || "Failed to load dashboard data.");
  } finally {
    setLoading(false);
  }
}, []);
useEffect(() => {
  const timer = setTimeout(() => {
    fetchDashboardData();
  }, 0);

  return () => clearTimeout(timer);
}, [fetchDashboardData]);

  const cards = [
    {
      title: "Total Orders",
      value: stats.orders.length,
      icon: ShoppingBag,
      color: "text-blue-600",
      bg: "bg-blue-50",
    },
    {
      title: "Total Products",
      value: stats.products,
      icon: Package,
      color: "text-green-600",
      bg: "bg-green-50",
    },
    {
      title: "Categories",
      value: stats.categories,
      icon: FolderOpen,
      color: "text-purple-600",
      bg: "bg-purple-50",
    },
    {
      title: "Paid Revenue",
      value: `${stats.revenue.toLocaleString("en-US")} EGP`,
      icon: Wallet,
      color: "text-orange-600",
      bg: "bg-orange-50",
    },
  ];
  const orderStatuses = [
    {
      name: "Confirmed",
      status: "confirmed",
      color: "text-blue-600",
      bg: "bg-blue-50",
    },
    {
      name: "Processing",
      status: "processing",
      color: "text-purple-600",
      bg: "bg-purple-50",
    },
    {
      name: "Shipped",
      status: "shipped",
      color: "text-indigo-600",
      bg: "bg-indigo-50",
    },
    {
      name: "Delivered",
      status: "delivered",
      color: "text-green-600",
      bg: "bg-green-50",
    },
    {
      name: "Cancelled",
      status: "cancelled",
      color: "text-red-600",
      bg: "bg-red-50",
    },
    {
      name: "Returned",
      status: "returned",
      color: "text-slate-600",
      bg: "bg-slate-100",
    },
  ];

  const getOrderStatusCount = (status) => {
    return stats.orders.filter((order) => order.status === status).length;
  };

  const salesLast7Days = Array.from({ length: 7 }, (_, index) => {
    const date = new Date();
    date.setHours(0, 0, 0, 0);
    date.setDate(date.getDate() - (6 - index));

    const dateKey = [
      date.getFullYear(),
      String(date.getMonth() + 1).padStart(2, "0"),
      String(date.getDate()).padStart(2, "0"),
    ].join("-");

    const total = stats.orders
      .filter((order) => {
        if (order.payment_status !== "paid") return false;

        const orderDate = new Date(order.created_at);

        const orderDateKey = [
          orderDate.getFullYear(),
          String(orderDate.getMonth() + 1).padStart(2, "0"),
          String(orderDate.getDate()).padStart(2, "0"),
        ].join("-");

        return orderDateKey === dateKey;
      })
      .reduce((sum, order) => sum + Number(order.total || 0), 0);

    return {
      date: date.toLocaleDateString("en-US", { weekday: "short" }),
      total,
    };
  });

  const maxDailySales = Math.max(...salesLast7Days.map((day) => day.total), 1);

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
  const handleRefresh = () => {
    setLoading(true);
    setError("");
    fetchDashboardData();
  };
  return (
    <main className="min-h-screen px-6 py-10">
      <div className="mx-auto max-w-7xl">
        <div className="mb-8 flex flex-wrap items-center justify-between gap-4">
          <div>
            <h1 className="text-3xl font-extrabold text-slate-900">
              Dashboard Overview
            </h1>

            <p className="mt-2 text-slate-500">
              Welcome to your ShopFlow admin dashboard.
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

        {loading ? (
          <div className="rounded-2xl border border-slate-200 bg-white p-10 text-center text-slate-500">
            Loading dashboard...
          </div>
        ) : (
          <>
            <div className="grid gap-6 sm:grid-cols-2 xl:grid-cols-4">
              {cards.map((card) => {
                const Icon = card.icon;

                return (
                  <div
                    key={card.title}
                    className="rounded-2xl border border-slate-200 bg-white p-6"
                  >
                    <div className="flex items-center justify-between">
                      <p className="font-medium text-slate-500">{card.title}</p>

                      <div className={`rounded-xl p-3 ${card.bg}`}>
                        <Icon size={22} className={card.color} />
                      </div>
                    </div>

                    <h2 className="mt-5 text-2xl font-extrabold text-slate-900">
                      {card.value}
                    </h2>
                  </div>
                );
              })}
            </div>

            <div className="mt-6 grid gap-6 lg:grid-cols-3">
              <div className="rounded-2xl border border-slate-200 bg-white p-6">
                <div className="flex items-center gap-3">
                  <div className="rounded-xl bg-yellow-50 p-3">
                    <Clock size={22} className="text-yellow-600" />
                  </div>

                  <div>
                    <p className="text-sm font-medium text-slate-500">
                      Pending Orders
                    </p>
                    <h3 className="text-2xl font-extrabold text-slate-900">
                      {stats.pending}
                    </h3>
                  </div>
                </div>
              </div>

              <div className="rounded-2xl border border-slate-200 bg-white p-6 lg:col-span-2">
                <div className="flex flex-wrap items-center justify-between gap-3">
                  <div>
                    <h2 className="text-lg font-bold text-slate-900">
                      Quick Actions
                    </h2>
                    <p className="mt-1 text-sm text-slate-500">
                      Manage your store easily.
                    </p>
                  </div>

                  <div className="flex flex-wrap gap-3">
                    <Link
                      to="/admin/products"
                      className="rounded-xl bg-blue-600 px-4 py-3 text-sm font-semibold text-white hover:bg-blue-700"
                    >
                      Manage Products
                    </Link>

                    <Link
                      to="/admin/orders"
                      className="rounded-xl border border-slate-200 px-4 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                    >
                      Manage Orders
                    </Link>
                  </div>
                </div>
              </div>
            </div>

            <div className="mt-8">
              <div className="mb-5">
                <h2 className="text-xl font-bold text-slate-900">
                  Orders by Status
                </h2>
                <p className="mt-1 text-sm text-slate-500">
                  Overview of your order statuses.
                </p>
              </div>

              <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                {orderStatuses.map((item) => (
                  <div
                    key={item.status}
                    className="rounded-2xl border border-slate-200 bg-white p-5"
                  >
                    <div className="flex items-center justify-between">
                      <p className="font-semibold text-slate-600">
                        {item.name}
                      </p>

                      <span
                        className={`rounded-lg px-3 py-2 text-sm font-bold ${item.bg} ${item.color}`}
                      >
                        {getOrderStatusCount(item.status)}
                      </span>
                    </div>

                    <div className="mt-4 h-2 overflow-hidden rounded-full bg-slate-100">
                      <div
                        className={`h-full rounded-full ${item.bg.replace("bg-", "bg-").replace("-50", "-500")}`}
                        style={{
                          width: `${
                            stats.orders.length > 0
                              ? (getOrderStatusCount(item.status) /
                                  stats.orders.length) *
                                100
                              : 0
                          }%`,
                        }}
                      />
                    </div>
                  </div>
                ))}
              </div>
            </div>

            <div className="mt-8 rounded-2xl border border-slate-200 bg-white p-6">
              <div className="mb-8">
                <h2 className="text-xl font-bold text-slate-900">
                  Sales Overview
                </h2>
                <p className="mt-1 text-sm text-slate-500">
                  Paid sales during the last 7 days.
                </p>
              </div>

              <div className="flex h-64 items-end justify-between gap-3 border-b border-slate-200 pb-2">
                {salesLast7Days.map((day) => (
                  <div
                    key={day.date}
                    className="flex h-full flex-1 flex-col items-center justify-end gap-2"
                  >
                    <span className="text-xs font-semibold text-slate-600">
                      {day.total.toLocaleString("en-US")}
                    </span>

                    <div className="flex h-full w-full items-end justify-center">
                      <div
                        className="w-full max-w-12 rounded-t-lg bg-blue-500 transition-all duration-300 hover:bg-blue-600"
                        style={{
                          height: `${(day.total / maxDailySales) * 85}%`,
                          minHeight: day.total > 0 ? "8px" : "0px",
                        }}
                      />
                    </div>

                    <span className="text-xs font-medium text-slate-500">
                      {day.date}
                    </span>
                  </div>
                ))}
              </div>

              <div className="mt-5 flex items-center justify-between">
                <span className="text-sm text-slate-500">Total paid sales</span>

                <span className="text-lg font-extrabold text-slate-900">
                  {salesLast7Days
                    .reduce((sum, day) => sum + day.total, 0)
                    .toLocaleString("en-US")}{" "}
                  EGP
                </span>
              </div>
            </div>

            <div className="mt-8 overflow-hidden rounded-2xl border border-slate-200 bg-white">
              <div className="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 p-6">
                <div>
                  <h2 className="text-xl font-bold text-slate-900">
                    Recent Orders
                  </h2>
                  <p className="mt-1 text-sm text-slate-500">
                    Latest customer orders.
                  </p>
                </div>

                <Link
                  to="/admin/orders"
                  className="text-sm font-semibold text-blue-600 hover:text-blue-700"
                >
                  View All Orders
                </Link>
              </div>

              {stats.orders.length === 0 ? (
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
                      {stats.orders.slice(0, 5).map((order) => (
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
                              className={`rounded-full px-3 py-1 text-xs font-bold capitalize ${getStatusColor(
                                order.status,
                              )}`}
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
          </>
        )}
      </div>
    </main>
  );
}
