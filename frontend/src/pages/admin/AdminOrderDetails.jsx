import { useCallback, useEffect, useState } from "react";
import { Link, useParams } from "react-router-dom";
import { ArrowLeft, Package, RefreshCw } from "lucide-react";
import api from "../../services/api";

const nextStatuses = {
  pending: ["confirmed", "cancelled"],
  confirmed: ["processing", "cancelled"],
  processing: ["shipped", "cancelled"],
  shipped: ["delivered"],
  delivered: ["returned"],
  returned: [],
  cancelled: [],
};

export default function AdminOrderDetails() {
  const { id } = useParams();

  const [order, setOrder] = useState(null);
  const [selectedStatus, setSelectedStatus] = useState("");
  const [loading, setLoading] = useState(true);
  const [updating, setUpdating] = useState(false);
  const [error, setError] = useState("");
  const [success, setSuccess] = useState("");
  const [selectedPaymentStatus, setSelectedPaymentStatus] = useState("");

  const fetchOrder = useCallback(async () => {
    try {
      const response = await api.get(`/admin/orders/${id}`);
      const data = response.data.data;

      setOrder(data);
      setSelectedStatus(data.status);
      setSelectedPaymentStatus(data.payment_status);
    } catch (err) {
      setError(err.response?.data?.message || "Failed to load order.");
    } finally {
      setLoading(false);
    }
  }, [id]);
  useEffect(() => {
    const timer = setTimeout(() => {
      fetchOrder();
    }, 0);

    return () => clearTimeout(timer);
  }, [fetchOrder]);
  const handleStatusUpdate = async () => {
    if (!selectedStatus || selectedStatus === order.status) return;

    setUpdating(true);
    setError("");
    setSuccess("");

    try {
      await api.patch(`/admin/orders/${id}/status`, {
        status: selectedStatus,
      });

      setSuccess("Order status updated successfully.");
      await fetchOrder();
    } catch (err) {
      setError(err.response?.data?.message || "Failed to update order status.");
    } finally {
      setUpdating(false);
    }
  };

  if (loading) {
    return (
      <div className="p-10 text-center text-slate-500">Loading order...</div>
    );
  }

  if (!order) {
    return (
      <div className="p-10 text-center">
        <p className="text-red-600">{error || "Order not found."}</p>
        <Link to="/admin/orders" className="mt-4 inline-block text-blue-600">
          Back to Orders
        </Link>
      </div>
    );
  }

  const handlePaymentUpdate = async () => {
    if (
      !selectedPaymentStatus ||
      selectedPaymentStatus === order.payment_status
    ) {
      return;
    }

    setUpdating(true);
    setError("");
    setSuccess("");

    try {
      await api.patch(`/admin/orders/${id}/payment-status`, {
        payment_status: selectedPaymentStatus,
      });

      setSuccess("Payment status updated successfully.");
      await fetchOrder();
    } catch (err) {
      setError(
        err.response?.data?.message || "Failed to update payment status.",
      );
    } finally {
      setUpdating(false);
    }
  };

  const availableStatuses = nextStatuses[order.status] || [];

  return (
    <div className="min-h-screen bg-slate-50">
      <main className="mx-auto max-w-5xl px-6 py-10">
        <Link
          to="/admin/orders"
          className="mb-6 inline-flex items-center gap-2 text-sm font-semibold text-slate-600 hover:text-blue-600"
        >
          <ArrowLeft size={17} />
          All Orders
        </Link>

        <div className="mb-8 flex items-center gap-3">
          <Package size={28} className="text-blue-600" />
          <div>
            <h1 className="text-3xl font-extrabold text-slate-900">
              Order #{order.order_number || order.id}
            </h1>
            <p className="mt-1 text-sm text-slate-500">
              {new Date(order.created_at).toLocaleString()}
            </p>
          </div>
        </div>

        {error && (
          <div className="mb-5 rounded-xl bg-red-50 p-4 text-red-700">
            {error}
          </div>
        )}

        {success && (
          <div className="mb-5 rounded-xl bg-green-50 p-4 text-green-700">
            {success}
          </div>
        )}

        <div className="mb-6 rounded-2xl border border-slate-200 bg-white p-6">
          <h2 className="mb-4 text-lg font-bold text-slate-900">
            Order Status
          </h2>

          <div className="flex flex-wrap items-center gap-3">
            <span className="rounded-full bg-blue-50 px-4 py-2 text-sm font-bold capitalize text-blue-700">
              {order.status}
            </span>

            {availableStatuses.length > 0 && (
              <>
                <select
                  value={selectedStatus}
                  onChange={(e) => setSelectedStatus(e.target.value)}
                  className="rounded-xl border border-slate-300 px-4 py-3 outline-none focus:border-blue-500"
                >
                  <option value={order.status}>Select new status</option>
                  {availableStatuses.map((status) => (
                    <option key={status} value={status}>
                      {status}
                    </option>
                  ))}
                </select>

                <button
                  onClick={handleStatusUpdate}
                  disabled={updating || selectedStatus === order.status}
                  className="flex items-center gap-2 rounded-xl bg-blue-600 px-5 py-3 font-semibold text-white hover:bg-blue-700 disabled:opacity-50"
                >
                  <RefreshCw size={16} />
                  {updating ? "Updating..." : "Update Status"}
                </button>
              </>
            )}

            {availableStatuses.length === 0 && (
              <span className="text-sm text-slate-500">
                This order has reached a final status.
              </span>
            )}
          </div>
        </div>

        <div className="mb-6 grid gap-6 md:grid-cols-2">
          <section className="rounded-2xl border border-slate-200 bg-white p-6">
            <h2 className="mb-4 text-lg font-bold text-slate-900">
              Customer Information
            </h2>
            <p className="mb-2 text-slate-700">
              <strong>Name:</strong> {order.customer_name}
            </p>
            <p className="mb-2 text-slate-700">
              <strong>Phone:</strong> {order.customer_phone}
            </p>
            <p className="mb-2 text-slate-700">
              <strong>City:</strong> {order.shipping_city}
            </p>
            <p className="text-slate-700">
              <strong>Address:</strong> {order.shipping_address}
            </p>
            {order.notes && (
              <p className="mt-3 text-slate-700">
                <strong>Notes:</strong> {order.notes}
              </p>
            )}
          </section>

          <section className="rounded-2xl border border-slate-200 bg-white p-6">
            <h2 className="mb-4 text-lg font-bold text-slate-900">
              Payment Information
            </h2>

            <p className="mb-4 text-slate-700">
              <strong>Method:</strong> {order.payment_method}
            </p>

            <p className="mb-5 text-slate-700">
              <strong>Payment Status:</strong>{" "}
              <span className="font-semibold capitalize">
                {order.payment_status}
              </span>
            </p>

            <div className="flex flex-wrap gap-3">
              <select
                value={selectedPaymentStatus}
                onChange={(e) => setSelectedPaymentStatus(e.target.value)}
                className="rounded-xl border border-slate-300 px-4 py-3 outline-none focus:border-blue-500"
              >
                <option value={order.payment_status}>
                  Select payment status
                </option>

                {order.status === "delivered" &&
                  order.payment_status === "unpaid" && (
                    <option value="paid">Paid</option>
                  )}

                {order.status === "returned" &&
                  order.payment_status === "paid" && (
                    <option value="refunded">Refunded</option>
                  )}
              </select>

              <button
                onClick={handlePaymentUpdate}
                disabled={
                  updating || selectedPaymentStatus === order.payment_status
                }
                className="rounded-xl bg-blue-600 px-5 py-3 font-semibold text-white hover:bg-blue-700 disabled:opacity-50"
              >
                {updating ? "Updating..." : "Update Payment"}
              </button>
            </div>
          </section>
        </div>

        <section className="rounded-2xl border border-slate-200 bg-white p-6">
          <h2 className="mb-5 text-lg font-bold text-slate-900">Order Items</h2>

          <div className="space-y-4">
            {order.items?.map((item) => (
              <div
                key={item.id}
                className="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 pb-4"
              >
                <div>
                  <p className="font-semibold text-slate-900">
                    {item.product_name}
                  </p>
                  <p className="text-sm text-slate-500">
                    SKU: {item.product_sku} · Quantity: {item.quantity}
                  </p>
                </div>

                <p className="font-bold text-slate-900">{item.total} EGP</p>
              </div>
            ))}
          </div>

          <div className="mt-6 space-y-3 border-t border-slate-200 pt-5">
            <div className="flex justify-between text-slate-600">
              <span>Subtotal</span>
              <span>{order.subtotal} EGP</span>
            </div>

            <div className="flex justify-between text-slate-600">
              <span>Shipping Fee</span>
              <span>{order.shipping_fee} EGP</span>
            </div>

            <div className="flex justify-between text-xl font-extrabold text-slate-900">
              <span>Total</span>
              <span>{order.total} EGP</span>
            </div>
          </div>
        </section>
      </main>
    </div>
  );
}
