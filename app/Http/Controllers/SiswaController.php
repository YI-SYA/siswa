<?php

namespace App\Http\Controllers;

use App\Exports\SiswaExport;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\PDF;
use Illuminate\Http\Request;

class SiswaController extends Controller
{
    public function index()
    {
        $data_siswa = \App\Models\Siswa::orderBy('nama_depan', 'asc')->get();;
        return view('siswa.index',  ['data_siswa' => $data_siswa]);
    }
    public function create(Request $request)
    {
        $data = $request->all();
        $check = \App\Models\Siswa::create($data);
        if (!$check) {
            $arr = array('msg' => 'Gagal simpan dengan Ajax', 'status' => false);
        } else {
            $arr = array('msg' => 'Sukses simpan dengan Ajax', 'status' => true);
        }
        return Response()->json($arr);
        return redirect('/siswa')->with('sukses', 'Data berhasil diinput.');
    }
    public function edit($id)
    {
        $siswa = \App\Models\Siswa::find($id);
        return view('siswa/edit', ['siswa' => $siswa]);
    }
    public function update(Request $request, $id)
    {
        $siswa = \App\Models\Siswa::find($id);
        $siswa->update($request->all());
        if ($request->hasFile('foto')) {
            $request->file('foto')->move('uploads/foto/', $request->file('foto')->getClientOriginalName());
            $siswa->foto = $request->file('foto')->getClientOriginalName();
            $siswa->save();
        }
        return redirect('/siswa')->with('sukses', 'Data berhasil di-update.');
    }
    public function delete($id)
    {
        $siswa = \App\Models\Siswa::find($id);
        $siswa->delete($siswa);
        return redirect('/siswa')->with('sukses', 'Data berhasil di-delete.');
    }

    public function profile($id)
    {
        $siswa = \App\Models\Siswa::find($id);
        $matapelajaran = \App\Models\Mapel::all();
        //Data Chart Grafik
        $categories =[];
        $data = [];
        foreach($matapelajaran as $mp){
        if($siswa->mapel()->wherePivot('mapel_id', $mp->id)->first()){
        $categories[] = $mp->nama;
        $data[] = $siswa->mapel()->wherePivot('mapel_id', $mp->id)->first()->pivot->nilai;
        }
        }
        return view('siswa.profile', ['siswa' => $siswa, 'matapelajaran'=> $matapelajaran,
        'categories'=> $categories, 'data'=> $data]);
    }

    public function exportExcel()
    {
        $nama_file = 'laporan_data_siswa_' . date('Y-m-d_H-i-s') . '.xlsx';
        return Excel::download(new SiswaExport, $nama_file);
    }
    public function pdf()
    {
        $data_siswa = \App\Models\Siswa::all();
        return view('siswa.pdf', ['data_siswa' => $data_siswa]);
    }
    public function exportPdf()
    {
        $data_siswa = \App\Models\Siswa::all();
        $pdf = PDF::loadView('siswa.pdf', ['data_siswa' => $data_siswa]);
        return $pdf->download('laporan_data_siswa_' . date('Y-m-d_H-i-s') . '.pdf');
    }
    public function addnilai(Request $request, $id)
    {
        $siswa = \App\Models\Siswa::find($id);
        //Validasi jika ada double data mata pelajaran yg diinput
        if($siswa->mapel()->where('mapel_id', $request->mapel)->exists())
        {
        return redirect('siswa/'.$id.'/profile')->with('error', 'Data Mata Pelajaran Sudah
        Ada');
        }
        $siswa->mapel()->attach($request->mapel, ['nilai' => $request->nilai]);
        return redirect('siswa/'.$id.'/profile')->with('sukses', 'Data Berhasil diupdate');
    }

}
