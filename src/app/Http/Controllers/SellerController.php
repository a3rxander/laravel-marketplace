<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSellerRequest;
use App\Http\Requests\UpdateSellerRequest;
use App\Http\Requests\ApproveSellerRequest;
use App\Models\Seller;
use App\Services\SellerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SellerController extends Controller
{
    public function __construct(
        private SellerService $sellerService
    ) {
        $this->middleware('auth:api');
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Seller::class);
        
        $sellers = $this->sellerService->getSellers($request->all());
        
        return response()->json([
            'success' => true,
            'data' => $sellers,
            'message' => 'Sellers retrieved successfully'
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $seller = $this->sellerService->getSellerById($id);
        $this->authorize('view', $seller);
        
        return response()->json([
            'success' => true,
            'data' => $seller,
            'message' => 'Seller retrieved successfully'
        ]);
    }

    public function store(StoreSellerRequest $request): JsonResponse
    { 
        $sellerData = $request->validated();
        $sellerData['user_id'] = auth()->id();
        
        $seller = $this->sellerService->createSeller($sellerData);
        
        return response()->json([
            'success' => true,
            'data' => $seller,
            'message' => 'Seller application submitted successfully'
        ], 201);
    }

    public function update(UpdateSellerRequest $request, int $id): JsonResponse
    {
        $seller = $this->sellerService->getSellerById($id);
        $this->authorize('update', $seller);
        
        $updatedSeller = $this->sellerService->updateSeller($id, $request->validated());
        
        return response()->json([
            'success' => true,
            'data' => $updatedSeller,
            'message' => 'Seller updated successfully'
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $seller = $this->sellerService->getSellerById($id);
        $this->authorize('delete', $seller);
        
        $this->sellerService->deleteSeller($id);
        
        return response()->json([
            'success' => true,
            'message' => 'Seller deleted successfully'
        ]);
    }

    public function myProfile(): JsonResponse
    {
        $seller = $this->sellerService->getSellerByUserId(auth()->id());
        
        if (!$seller) {
            return response()->json([
                'success' => false,
                'message' => 'Seller profile not found'
            ], 404);
        }
        
        return response()->json([
            'success' => true,
            'data' => $seller,
            'message' => 'Seller profile retrieved successfully'
        ]);
    }

    public function updateProfile(UpdateSellerRequest $request): JsonResponse
    {
        $seller = $this->sellerService->getSellerByUserId(auth()->id());
        
        if (!$seller) {
            return response()->json([
                'success' => false,
                'message' => 'Seller profile not found'
            ], 404);
        }
        
        $this->authorize('update', $seller);
        
        $updatedSeller = $this->sellerService->updateSeller($seller->id, $request->validated());
        
        return response()->json([
            'success' => true,
            'data' => $updatedSeller,
            'message' => 'Seller profile updated successfully'
        ]);
    }

    public function approve(ApproveSellerRequest $request, int $id): JsonResponse
    {
        $seller = $this->sellerService->getSellerById($id);
        $this->authorize('approve', $seller);
        
        $approvedSeller = $this->sellerService->approveSeller(
            $id,
            $request->validated(),
            auth()->id()
        );
        
        return response()->json([
            'success' => true,
            'data' => $approvedSeller,
            'message' => 'Seller approved successfully'
        ]);
    }

    public function reject(Request $request, int $id): JsonResponse
    {
        $seller = $this->sellerService->getSellerById($id);
        $this->authorize('reject', $seller);
        
        $request->validate([
            'rejection_reason' => 'required|string|max:1000'
        ]);
        
        $rejectedSeller = $this->sellerService->rejectSeller($id, $request->rejection_reason);
        
        return response()->json([
            'success' => true,
            'data' => $rejectedSeller,
            'message' => 'Seller rejected successfully'
        ]);
    }

    public function suspend(Request $request, int $id): JsonResponse
    {
        $seller = $this->sellerService->getSellerById($id);
        $this->authorize('suspend', $seller);
        
        $request->validate([
            'suspension_reason' => 'nullable|string|max:1000'
        ]);
        
        $suspendedSeller = $this->sellerService->suspendSeller($id, $request->suspension_reason);
        
        return response()->json([
            'success' => true,
            'data' => $suspendedSeller,
            'message' => 'Seller suspended successfully'
        ]);
    }

    public function reactivate(int $id): JsonResponse
    {
        $seller = $this->sellerService->getSellerById($id);
        $this->authorize('reactivate', $seller);
        
        $reactivatedSeller = $this->sellerService->reactivateSeller($id);
        
        return response()->json([
            'success' => true,
            'data' => $reactivatedSeller,
            'message' => 'Seller reactivated successfully'
        ]);
    }

    public function dashboard(): JsonResponse
    {
        $seller = $this->sellerService->getSellerByUserId(auth()->id());
        
        if (!$seller) {
            return response()->json([
                'success' => false,
                'message' => 'Seller profile not found'
            ], 404);
        }
        
        $dashboard = $this->sellerService->getSellerDashboard($seller->id);
        
        return response()->json([
            'success' => true,
            'data' => $dashboard,
            'message' => 'Seller dashboard retrieved successfully'
        ]);
    }

    public function sales(Request $request): JsonResponse
    {
        $seller = $this->sellerService->getSellerByUserId(auth()->id());
        
        if (!$seller) {
            return response()->json([
                'success' => false,
                'message' => 'Seller profile not found'
            ], 404);
        }
        
        $sales = $this->sellerService->getSellerSales($seller->id, $request->all());
        
        return response()->json([
            'success' => true,
            'data' => $sales,
            'message' => 'Sales data retrieved successfully'
        ]);
    }
}