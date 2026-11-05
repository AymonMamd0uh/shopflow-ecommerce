import { BrowserRouter, Routes, Route } from "react-router-dom";
import Products from "./pages/Products";
import Login from "./pages/Login";
import Register from "./pages/Register";
import Cart from "./pages/Cart";
import Checkout from "./pages/Checkout";
import MyOrders from "./pages/MyOrders";
import OrderDetails from "./pages/OrderDetails";

import AdminRoute from "./components/AdminRoute";

import AdminOrders from "./pages/admin/AdminOrders";
import AdminOrderDetails from "./pages/admin/AdminOrderDetails";
import AdminProducts from "./pages/admin/AdminProducts";
import CreateProduct from "./pages/admin/CreateProduct";
import EditProduct from "./pages/admin/EditProduct";
import AdminCategories from "./pages/admin/AdminCategories";
import CreateCategory from "./pages/admin/CreateCategory";
import EditCategory from "./pages/admin/EditCategory";

import { CartProvider } from "./context/CartContext.jsx";
import AdminLayout from "./components/AdminLayout";
import AdminDashboard from "./pages/admin/AdminDashboard";
function App() {
  return (
    <BrowserRouter>
      <CartProvider>
        <Routes>
          {/* Public Routes */}
          <Route path="/" element={<Products />} />
          <Route path="/login" element={<Login />} />
          <Route path="/register" element={<Register />} />
          <Route path="/cart" element={<Cart />} />
          <Route path="/checkout" element={<Checkout />} />

          {/* Customer Routes */}
          <Route path="/my-orders" element={<MyOrders />} />
          <Route path="/orders/:id" element={<OrderDetails />} />

          {/* Protected Admin Routes */}
          <Route element={<AdminRoute />}>
            <Route element={<AdminLayout />}>
              <Route path="/admin/dashboard" element={<AdminDashboard />} />
              <Route path="/admin/orders" element={<AdminOrders />} />
              <Route path="/admin/orders/:id" element={<AdminOrderDetails />} />

              <Route path="/admin/products" element={<AdminProducts />} />
              <Route
                path="/admin/products/create"
                element={<CreateProduct />}
              />
              <Route
                path="/admin/products/:id/edit"
                element={<EditProduct />}
              />

              <Route path="/admin/categories" element={<AdminCategories />} />
              <Route
                path="/admin/categories/create"
                element={<CreateCategory />}
              />
              <Route
                path="/admin/categories/:id/edit"
                element={<EditCategory />}
              />
            </Route>
          </Route>
        </Routes>
      </CartProvider>
    </BrowserRouter>
  );
}

export default App;
