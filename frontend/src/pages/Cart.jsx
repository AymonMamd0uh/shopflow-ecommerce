import { useState } from "react";
import { Link } from "react-router-dom";
import { Minus, Plus, Trash2, ShoppingBag, ArrowLeft } from "lucide-react";
import Navbar from "../components/Navbar";
import { useCart } from "../context/useCart";

function Cart() {
  const { cart, loading, updateCartItem, removeCartItem } = useCart();

  const [busyId, setBusyId] = useState(null);

  const handleUpdate = async (itemId, quantity) => {
    if (quantity < 1) return;

    try {
      setBusyId(itemId);
      await updateCartItem(itemId, quantity);
    } catch (error) {
      alert(error.response?.data?.message || "Failed to update quantity.");
    } finally {
      setBusyId(null);
    }
  };

  const handleRemove = async (itemId) => {
    try {
      setBusyId(itemId);
      await removeCartItem(itemId);
    } catch (error) {
      alert(error.response?.data?.message || "Failed to remove product.");
    } finally {
      setBusyId(null);
    }
  };

  const items = cart?.items || [];

  return (
    <div className="min-h-screen bg-slate-50">
      <Navbar />

      <main className="mx-auto max-w-6xl px-4 py-10 sm:px-6">
        <div className="mb-8">
          <Link
            to="/"
            className="mb-4 inline-flex items-center gap-2 text-sm text-blue-600 hover:text-blue-700"
          >
            <ArrowLeft size={16} />
            Continue Shopping
          </Link>

          <h1 className="text-3xl font-bold text-slate-900">Shopping Cart</h1>
          <p className="mt-2 text-slate-500">
            Review your items before checkout.
          </p>
        </div>

        {loading ? (
          <p className="py-16 text-center text-slate-500">Loading cart...</p>
        ) : items.length === 0 ? (
          <div className="rounded-2xl border border-slate-200 bg-white py-20 text-center">
            <ShoppingBag className="mx-auto mb-4 text-slate-300" size={56} />
            <h2 className="text-xl font-bold text-slate-800">
              Your cart is empty
            </h2>
            <p className="mt-2 text-slate-500">
              Start adding products to your cart.
            </p>
            <Link
              to="/"
              className="mt-6 inline-block rounded-xl bg-blue-600 px-6 py-3 font-semibold text-white hover:bg-blue-700"
            >
              Browse Products
            </Link>
          </div>
        ) : (
          <div className="grid gap-8 lg:grid-cols-3">
            <div className="space-y-4 lg:col-span-2">
              {items.map((item) => (
                <div
                  key={item.id}
                  className="flex flex-col gap-5 rounded-2xl border border-slate-200 bg-white p-5 sm:flex-row sm:items-center"
                >
                  <div className="flex h-24 w-24 shrink-0 items-center justify-center rounded-xl bg-blue-50">
                    <ShoppingBag size={36} className="text-blue-500" />
                  </div>

                  <div className="min-w-0 flex-1">
                    <h3 className="font-bold text-slate-900">
                      {item.product.name}
                    </h3>
                    <p className="mt-1 text-sm text-slate-500">
                      {item.product.sku}
                    </p>
                    <p className="mt-2 font-semibold text-blue-600">
                      {Number(item.product.price).toLocaleString("en-US")} EGP
                    </p>
                  </div>

                  <div className="flex items-center justify-between gap-5 sm:flex-col sm:items-end">
                    <div className="flex items-center gap-3">
                      <button
                        onClick={() => handleUpdate(item.id, item.quantity - 1)}
                        disabled={busyId === item.id || item.quantity <= 1}
                        className="rounded-lg border border-slate-200 p-2 hover:bg-slate-50 disabled:opacity-40"
                      >
                        <Minus size={16} />
                      </button>

                      <span className="min-w-5 text-center font-semibold">
                        {item.quantity}
                      </span>

                      <button
                        onClick={() => handleUpdate(item.id, item.quantity + 1)}
                        disabled={
                          busyId === item.id ||
                          item.quantity >= item.product.stock_quantity
                        }
                        className="rounded-lg border border-slate-200 p-2 hover:bg-slate-50 disabled:opacity-40"
                      >
                        <Plus size={16} />
                      </button>
                    </div>

                    <div className="flex items-center gap-3">
                      <span className="font-bold text-slate-900">
                        {Number(item.subtotal).toLocaleString("en-US")} EGP
                      </span>

                      <button
                        onClick={() => handleRemove(item.id)}
                        disabled={busyId === item.id}
                        className="rounded-lg p-2 text-red-500 hover:bg-red-50 disabled:opacity-40"
                        title="Remove item"
                      >
                        <Trash2 size={18} />
                      </button>
                    </div>
                  </div>
                </div>
              ))}
            </div>

            <div className="h-fit rounded-2xl border border-slate-200 bg-white p-6">
              <h2 className="text-xl font-bold text-slate-900">
                Order Summary
              </h2>

              <div className="mt-6 flex justify-between text-slate-600">
                <span>Items ({items.length})</span>
                <span>{Number(cart.total).toLocaleString("en-US")} EGP</span>
              </div>

              <div className="my-5 border-t border-slate-200" />

              <div className="flex justify-between text-lg font-bold text-slate-900">
                <span>Total</span>
                <span>{Number(cart.total).toLocaleString("en-US")} EGP</span>
              </div>

              <button
                onClick={() => (window.location.href = "/checkout")}
                className="mt-6 w-full rounded-xl bg-blue-600 py-3 font-semibold text-white hover:bg-blue-700"
              >
                Proceed to Checkout
              </button>

              <p className="mt-3 text-center text-xs text-slate-400">
                Checkout will be available in the next step.
              </p>
            </div>
          </div>
        )}
      </main>
    </div>
  );
}

export default Cart;
