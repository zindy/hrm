info {title {Batch Processing template} version 2.2 templateName batch_2013-07-01_03-43-27 date {Mon Jul 1 15:43:27 CEST 2013}}
taskList {setEnv  taskID:0}
setEnv {resultDir {resultdir} perJobThreadCnt auto concurrentJobCnt 1 OMP_DYNAMIC 1 timeOut 10000 exportFormat {type ics multidir 0 cmode scale}}
 taskID:0 {info {state readyToRun tag {setp Micr decon Decon} timeStartAbs 1372686207 timeOut 10000 userDefConfidence default} taskList {imgOpen setp adjbl cmle:0 previewGen:0 previewGen:1 previewGen:2 previewGen:3 previewGen:4 previewGen:5 previewGen:6 previewGen:7 previewGen:8 previewGen:9 previewGen:10 previewGen:11 imgSave} imgOpen {path {bad.lsm} series off index  0} setp {completeChanCnt 1 micr {confocal} parState,micr {noMetaData} s {0.05 0.05 0.05 *} parState,s {noMetaData noMetaData noMetaData default} iFacePrim 0.0 parState,iFacePrim default iFaceScnd 0.0 parState,iFaceScnd default pr {250} parState,pr {noMetaData} imagingDir {upward} parState,imagingDir {noMetaData} objQuality {good} parState,objQuality {default} pcnt {1} parState,pcnt {noMetaData} ex {491} parState,ex {noMetaData} em {520} parState,em {noMetaData} exBeamFill {2.0} parState,exBeamFill {default} ri {1.47} parState,ri {noMetaData} ril {*} parState,ril {default} na {*} parState,na {default}} adjbl {enabled 0 ni 0}  cmle:0 {q 0.1 brMode one it 10 bgMode auto bg 0 sn 10 blMode auto pad auto psfMode auto psfPath {} timeOut 36000 mode fast}    previewGen:0 {image raw destDir {previews} destFile {bad.lsm} type XYXZ size 400}   previewGen:1 {image raw destDir {previews} destFile {bad.lsm} type XYXZ size 400}  previewGen:2 {image deconvolved destDir {previews} destFile {51d1877ced82f_hrm.ics} type XYXZ size 400}  previewGen:3 {image raw destDir {previews} destFile {51d1877ced82f_hrm.ics.original} type orthoSlice size 400}  previewGen:4 {image deconvolved destDir {previews} destFile {51d1877ced82f_hrm.ics} type orthoSlice size 400}  previewGen:5 {image deconvolved destDir {previews} destFile {51d1877ced82f_hrm.ics.stack} type ZMovie size 400}  previewGen:6 {image deconvolved destDir {previews} destFile {51d1877ced82f_hrm.ics.tSeries} type timeMovie size 400}  previewGen:7 {image deconvolved destDir {previews} destFile {51d1877ced82f_hrm.ics.tSeries.sfp} type timeSFPMovie size 400}  previewGen:8 {image raw destDir {previews} destFile {51d1877ced82f_hrm.ics.original.sfp} type SFP size 400}  previewGen:9 {image deconvolved destDir {previews} destFile {51d1877ced82f_hrm.ics.sfp} type SFP size 400}  previewGen:10 {destDir {previews} destFile {51d1877ced82f_hrm.ics} type compareZStrips size 400}  previewGen:11 {destDir {previews} destFile {51d1877ced82f_hrm.ics} type compareTStrips size 400}  imgSave {rootName {bad_51d1877ced82f_hrm}}}