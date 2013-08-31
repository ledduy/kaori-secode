kaori-secode
============

KAORI-SECODE - A Framework for Semantic Concept Detection

1. Purpose
- Test new local feature implementations using ImageCLEF dataset.
  + Combine the steps of raw feature extraction, quantization, and encoding in ONE job so that it is more efficient when working on the grid.
  + Check whether normalization is needed in encoding. Currently, no normalization is used. However, in kaori-ins, it is showed that normalization improves the matching performance.
  + Check the effect of codebook size. Currently, it is fixed to 500.
  + Check soft-assignment in encoding method. Currently, some parameters, e.g norm of L2 distance of each feature point are fixed in an adhoc way
- Adding more implementations, for example, fisher vector, LLC.
2. 
